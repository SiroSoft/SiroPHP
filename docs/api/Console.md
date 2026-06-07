---
title: C on so le
description: SiroPHP C on so le reference
sidebar_position: 4
sidebar_label: C on so le
---

# Console & Custom Commands Reference

## Overview

The Console class runs Siro's CLI. You can register custom commands and run them via `php siro <command>`.

```php
use Siro\Core\Console;
```

---

## Register Custom Command

### 1. Create a command class

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use Siro\Core\Commands\CommandInterface;
use Siro\Core\Commands\CommandSupport;

final class GenerateReportCommand implements CommandInterface
{
    use CommandSupport;

    public function run(array $args): int
    {
        $this->write('Generating report...');

        // Your logic here
        $format = $args[0] ?? 'csv';

        $this->write("  ✓ Report generated in {$format} format");
        return 0;  // 0 = success, 1 = error
    }
}
```

### 2. Register in `routes/api.php`

```php
Console::registerCommand(
    'report:generate',         // command name
    GenerateReportCommand::class,  // handler class
    'Generate sales report',       // description
);
```

### 3. Run it

```bash
php siro report:generate
php siro report:generate pdf
php siro report:generate --help
```

---

## Command Support Methods

The `CommandSupport` trait provides helpers for CLI interaction:

```php
class MyCommand implements CommandInterface
{
    use CommandSupport;

    public function run(array $args): int
    {
        $this->write('Processing...');           // Write line
        $this->write('  ✓ Done', 'green');       // With color
        $this->error('Something went wrong');    // Red text

        $name = $this->ask('Enter your name');   // Prompt input
        $confirm = $this->confirm('Continue?');  // Yes/no prompt

        return 0;
    }
}
```

---

## Bulk Registration

```php
Console::registerCommands([
    'report:generate' => [
        'class' => GenerateReportCommand::class,
        'description' => 'Generate sales report',
    ],
    'data:import' => [
        'class' => ImportDataCommand::class,
        'description' => 'Import data from CSV',
    ],
]);
```

---

## Built-in Version

```php
$version = Console::getVersion();  // "0.34.0"
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `Console::registerCommand(name, class, description)` | Register a CLI command |
| `Console::registerCommands(array $commands)` | Register multiple commands |
| `Console::getVersion()` | Get framework version |
| `$cmd->run(array $args)` | Execute the command |
| `$cmd->write(string $text, string $color)` | Write to stdout |
| `$cmd->error(string $text)` | Write to stderr (red) |
| `$cmd->ask(string $question)` | Prompt for input |
| `$cmd->confirm(string $question)` | Yes/no prompt |
