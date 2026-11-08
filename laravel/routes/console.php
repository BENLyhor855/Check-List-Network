<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('rac:prepare', function () {
    $pdo = DB::connection()->getPdo();
    if ((int) $pdo->query("SELECT GET_LOCK('racnoc_migrations',30)")->fetchColumn() !== 1) throw new RuntimeException('Migration lock unavailable');
    try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations(version VARCHAR(120) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version=?');
        $stmt->execute(['001_foundation.sql']);
        if (!(int) $stmt->fetchColumn()) {
            foreach (explode(';', file_get_contents(database_path('schema/001_foundation.sql'))) as $sql) if (trim($sql) !== '') $pdo->exec($sql);
            $stmt = $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(?)'); $stmt->execute(['001_foundation.sql']);
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
            $email = env('BOOTSTRAP_EMAIL'); $password = env('BOOTSTRAP_PASSWORD');
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || !$password || strlen($password) < 14 || strlen($password) > 72) throw new RuntimeException('First startup requires a valid bootstrap email and 14–72 byte password');
            $stmt = $pdo->prepare('INSERT INTO users(email,password_hash,role_id) VALUES(?,?,1)');
            $stmt->execute([strtolower($email), password_hash($password, PASSWORD_BCRYPT, ['cost'=>12])]);
        }
    } finally { $pdo->query("SELECT RELEASE_LOCK('racnoc_migrations')"); }
    $this->info('Database prepared.');
});
