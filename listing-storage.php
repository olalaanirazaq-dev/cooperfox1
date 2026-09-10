<?php

declare(strict_types=1);

function cooperFoxStorageDirectory(): string
{
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the Cooper Fox storage directory.');
    }
    return $directory;
}

function cooperFoxDbPath(): string
{
    return cooperFoxStorageDirectory() . DIRECTORY_SEPARATOR . 'cooper_fox.sqlite';
}

function cooperFoxLegacyListingsPath(): string
{
    return cooperFoxStorageDirectory() . DIRECTORY_SEPARATOR . 'listings.json';
}

function cooperFoxOpenDatabase(): ?PDO
{
    if (!class_exists('PDO')) {
        return null;
    }

    $dbPath = cooperFoxDbPath();
    $dsn = 'sqlite:' . $dbPath;

    try {
        $db = new PDO($dsn);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA journal_mode=WAL;');
        $db->exec('PRAGMA foreign_keys = ON;');
        $db->exec('CREATE TABLE IF NOT EXISTS listings (id INTEGER PRIMARY KEY AUTOINCREMENT, sort_order INTEGER NOT NULL DEFAULT 0, payload TEXT NOT NULL);');
        return $db;
    } catch (Throwable $exception) {
        error_log('Cooper Fox SQLite setup failed: ' . $exception->getMessage());
        return null;
    }
}

function cooperFoxLegacyMappingExists(): bool
{
    $legacyPath = cooperFoxLegacyListingsPath();
    return is_file($legacyPath) && filesize($legacyPath) > 0;
}

function cooperFoxMigrateLegacyListings(): void
{
    $db = cooperFoxOpenDatabase();
    if ($db === null) {
        return;
    }

    $count = (int) $db->query('SELECT COUNT(*) AS total FROM listings')->fetchColumn();
    if ($count > 0) {
        $db = null;
        return;
    }

    $legacyPath = cooperFoxLegacyListingsPath();
    if (!is_file($legacyPath)) {
        $db = null;
        return;
    }

    $raw = file_get_contents($legacyPath);
    $legacy = json_decode($raw !== false ? $raw : '[]', true);
    if (!is_array($legacy)) {
        $db = null;
        return;
    }

    $insert = $db->prepare('INSERT INTO listings (sort_order, payload) VALUES (:sort_order, :payload)');
    foreach ($legacy as $index => $listing) {
        if (!is_array($listing)) {
            continue;
        }

        $insert->execute([
            ':sort_order' => $index,
            ':payload' => json_encode($listing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    $db = null;
}

function cooperFoxReadListings(): array
{
    $db = cooperFoxOpenDatabase();
    if ($db === null) {
        $legacyPath = cooperFoxLegacyListingsPath();
        if (!is_file($legacyPath)) {
            return [];
        }

        $raw = file_get_contents($legacyPath);
        $legacy = json_decode($raw !== false ? $raw : '[]', true);
        return is_array($legacy) ? array_values($legacy) : [];
    }

    cooperFoxMigrateLegacyListings();

    $rows = $db->query('SELECT payload FROM listings ORDER BY sort_order ASC, id ASC');
    $listings = [];
    foreach ($rows as $row) {
        $payload = $row['payload'] ?? '[]';
        $decoded = json_decode((string) $payload, true);
        if (is_array($decoded)) {
            $listings[] = $decoded;
        }
    }
    $db = null;

    if ($listings !== []) {
        return array_values($listings);
    }

    if (!cooperFoxLegacyMappingExists()) {
        return [];
    }

    $legacyPath = cooperFoxLegacyListingsPath();
    $raw = file_get_contents($legacyPath);
    $legacy = json_decode($raw !== false ? $raw : '[]', true);
    return is_array($legacy) ? array_values($legacy) : [];
}

function cooperFoxWriteListings(array $listings): bool
{
    $db = cooperFoxOpenDatabase();
    if ($db === null) {
        $legacyPath = cooperFoxLegacyListingsPath();
        $directory = dirname($legacyPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }
        return file_put_contents($legacyPath, json_encode(array_values($listings), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
    }

    try {
        $db->beginTransaction();
        $db->exec('DELETE FROM listings');
        $insert = $db->prepare('INSERT INTO listings (sort_order, payload) VALUES (:sort_order, :payload)');

        foreach (array_values($listings) as $index => $listing) {
            if (!is_array($listing)) {
                continue;
            }

            $insert->execute([
                ':sort_order' => $index,
                ':payload' => json_encode($listing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]);
        }

        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Cooper Fox listing write failed: ' . $exception->getMessage());
        $db = null;
        return false;
    }

    $db = null;

    $legacyPath = cooperFoxLegacyListingsPath();
    $directory = dirname($legacyPath);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        return false;
    }

    return file_put_contents($legacyPath, json_encode(array_values($listings), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}
