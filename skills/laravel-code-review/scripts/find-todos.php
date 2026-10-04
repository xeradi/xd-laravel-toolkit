<?php

/**
 * List TODO-style markers found in comments of .php files (Blade included).
 *
 * Uses PHP's tokenizer, so only real comments match — not class names,
 * strings, or variables like `Todo::class` or `'todo'`.
 *
 * Markers are matched in UPPERCASE (`TODO`, `FIXME`) or as a phpDoc tag
 * (`@todo`), so prose like "the user's todo items" is not reported.
 * Pass --ignore-case to match any casing.
 *
 * Usage:
 *   php find-todos.php [path=.] [--markers=TODO,FIXME] [--format=table|json] [--ignore-case]
 *
 * `path` may be a directory or a single file.
 * Skips: vendor, node_modules, storage, bootstrap/cache, .git, public/build.
 * Requires PHP 8.0+.
 */

if (PHP_VERSION_ID < 80000) {
    fwrite(STDERR, "find-todos.php requires PHP 8.0 or newer.\n");
    exit(1);
}

$path = '.';
$markers = ['TODO'];
$format = 'table';
$ignoreCase = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        echo "Usage: php find-todos.php [path=.] [--markers=TODO,FIXME] [--format=table|json] [--ignore-case]\n";
        exit(0);
    } elseif (str_starts_with($arg, '--markers=')) {
        $markers = array_values(array_filter(array_map('trim', explode(',', substr($arg, 10)))));
    } elseif (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    } elseif ($arg === '--ignore-case') {
        $ignoreCase = true;
    } elseif (str_starts_with($arg, '--')) {
        fwrite(STDERR, "Unknown option: {$arg}\n");
        exit(1);
    } else {
        $path = $arg;
    }
}

if (! in_array($format, ['table', 'json'], true)) {
    fwrite(STDERR, "Unknown format: {$format} (use table or json)\n");
    exit(1);
}

if ($markers === []) {
    fwrite(STDERR, "No markers given.\n");
    exit(1);
}

$skipDirs = ['vendor', 'node_modules', 'storage', '.git', 'bootstrap/cache', 'public/build'];

$alternatives = implode('|', array_map(fn ($m) => preg_quote($m, '/'), $markers));
$markerPattern = $ignoreCase
    ? '/(?<![\w@])@?(' . $alternatives . ')\b[\s:\-–—(]*(.*)$/iu'
    : '/(?<![\w@])(?:@(?i:(' . $alternatives . '))|(' . strtoupper($alternatives) . '))\b[\s:\-–—(]*(.*)$/u';

function isSkipped(string $relative, array $skipDirs): bool
{
    foreach ($skipDirs as $dir) {
        if ($relative === $dir || str_starts_with($relative, $dir . '/') || str_contains($relative, '/' . $dir . '/') || str_ends_with($relative, '/' . $dir)) {
            return true;
        }
    }

    return false;
}

function cleanLine(string $line): string
{
    $line = preg_replace('#^\s*(//|\#|/\*+|\*+/?|\{\{--|<!--)\s*#', '', $line);
    $line = preg_replace('#\s*(\*/|--\}\}|-->)\s*$#', '', $line);

    return trim($line);
}

/** @return array<int, array{line:int, text:string}> */
function commentsFromTokens(string $code): array
{
    $comments = [];
    foreach (@token_get_all($code) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            $comments[] = ['line' => $token[2], 'text' => $token[1]];
        }
    }

    return $comments;
}

/**
 * Blade / HTML comments live in inline HTML, which the tokenizer doesn't split.
 *
 * @return array<int, array{line:int, text:string}>
 */
function commentsFromBlade(string $code): array
{
    $comments = [];
    preg_match_all('/\{\{--.*?--\}\}|<!--.*?-->/s', $code, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as [$text, $offset]) {
        $comments[] = ['line' => substr_count($code, "\n", 0, $offset) + 1, 'text' => $text];
    }

    // PHP comments inside @php ... @endphp blocks (not the inline @php(...) form).
    preg_match_all('/@php\b(?!\s*\()(.*?)@endphp/s', $code, $blocks, PREG_OFFSET_CAPTURE);
    foreach ($blocks[1] as [$body, $offset]) {
        $startLine = substr_count($code, "\n", 0, $offset) + 1;
        foreach (commentsFromTokens("<?php\n" . $body) as $comment) {
            $comment['line'] += $startLine - 2;
            $comments[] = $comment;
        }
    }

    return $comments;
}

/** @return iterable<string> */
function phpFiles(string $root, array $skipDirs): iterable
{
    if (is_file($root)) {
        yield $root;

        return;
    }

    // Prune skipped directories instead of walking them (node_modules can be huge).
    $directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
    $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $file) use ($root, $skipDirs) {
        $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');

        return $file->isDir()
            ? ! isSkipped($relative, $skipDirs)
            : str_ends_with($relative, '.php');
    });

    foreach (new RecursiveIteratorIterator($filter) as $file) {
        yield $file->getPathname();
    }
}

$root = realpath($path);
if ($root === false) {
    fwrite(STDERR, "Path not found: {$path}\n");
    exit(1);
}

// Report paths relative to the working directory so they work with `git blame` and editors.
$cwd = getcwd();
$base = ($cwd !== false && ($root === $cwd || str_starts_with($root, $cwd . DIRECTORY_SEPARATOR))) ? $cwd : '';
$results = [];

foreach (phpFiles($root, $skipDirs) as $file) {
    $code = @file_get_contents($file);
    if ($code === false) {
        fwrite(STDERR, "Skipped unreadable file: {$file}\n");
        continue;
    }

    $comments = commentsFromTokens($code);
    if (str_ends_with($file, '.blade.php')) {
        $comments = array_merge($comments, commentsFromBlade($code));
    }

    foreach ($comments as $comment) {
        foreach (explode("\n", $comment['text']) as $i => $rawLine) {
            if (preg_match($markerPattern, cleanLine($rawLine), $m) !== 1) {
                continue;
            }

            $marker = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
            $text = trim(end($m));

            $results[] = [
                'file' => $base === '' ? str_replace('\\', '/', $file) : ltrim(str_replace('\\', '/', substr($file, strlen($base))), '/'),
                'line' => $comment['line'] + $i,
                'marker' => strtoupper($marker),
                'text' => $text !== '' ? $text : '(no description)',
            ];
        }
    }
}

usort($results, fn ($a, $b) => [$a['file'], $a['line']] <=> [$b['file'], $b['line']]);

if ($format === 'json') {
    echo json_encode(
        $results,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ) . "\n";
    exit(0);
}

foreach ($results as $r) {
    echo "{$r['file']}:{$r['line']}\t{$r['marker']}\t{$r['text']}\n";
}
fwrite(STDERR, count($results) . " marker(s) found.\n");
