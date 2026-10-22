<?php
declare(strict_types=1);
// Local process lifecycle helper. It sends no HTTP requests.
$root = dirname(__DIR__);
$router = $root . '/servers/http-office-router.php';
$storage = $root . '/storage';
$pidFile = $storage . '/http-office.pid';
$bind = '127.0.0.1:48392';
$action = $argv[1] ?? '';
if (!in_array($action, ['start', 'stop'], true)) {
    throw new RuntimeException('Choose start or stop.');
}
if (!is_dir($storage)) {
    mkdir($storage, 0700, true);
}
$pid = is_file($pidFile) ? (int) file_get_contents($pidFile) : 0;
$alive = $pid > 0 && posix_kill($pid, 0);
if ($alive) {
    $command = shell_exec('ps -p ' . $pid . ' -o args=') ?? '';
    if (!str_contains($command, $router) || !str_contains($command, $bind)) {
        throw new RuntimeException('PID does not belong to this fixture.');
    }
}
if ($action === 'stop') {
    if ($alive) {
        posix_kill($pid, SIGTERM);
        for ($i = 0; $i < 30 && posix_kill($pid, 0); $i++) {
            usleep(50_000);
        }
        if (posix_kill($pid, 0)) {
            throw new RuntimeException('Fixture did not stop in time.');
        }
    }
    if (is_file($pidFile)) {
        unlink($pidFile);
    }
    echo 'HTTP fixture stopped.' . PHP_EOL;
    exit;
}
if (!$alive) {
    $socket = @fsockopen('127.0.0.1', 48392, $number, $reason, 0.2);
    if (is_resource($socket)) {
        fclose($socket);
        throw new RuntimeException('Fixture port is already occupied.');
    }
    if (!function_exists('pcntl_fork') || !function_exists('posix_setsid')) {
        throw new RuntimeException('This studio helper needs pcntl and posix.');
    }
    $pid = pcntl_fork();
    if ($pid < 0) {
        throw new RuntimeException('Fixture process did not start.');
    }
    if ($pid === 0) {
        posix_setsid();
        foreach ([STDIN, STDOUT, STDERR] as $stream) {
            fclose($stream);
        }
        $command = 'exec ' . escapeshellarg(PHP_BINARY) . ' -S '
            . escapeshellarg($bind) . ' ' . escapeshellarg($router)
            . ' </dev/null >>' . escapeshellarg($storage . '/http-office.log')
            . ' 2>&1';
        pcntl_exec('/bin/sh', ['-c', $command]);
        exit(1);
    }
    file_put_contents($pidFile, (string) $pid);
}
for ($i = 0; $i < 60; $i++) {
    $socket = @fsockopen('127.0.0.1', 48392, $number, $reason, 0.2);
    if (is_resource($socket)) {
        fclose($socket);
        echo 'HTTP fixture started.' . PHP_EOL;
        exit;
    }
    usleep(50_000);
}
throw new RuntimeException('Fixture did not become ready.');
