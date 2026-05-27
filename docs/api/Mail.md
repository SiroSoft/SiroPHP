---
title: M ai l
description: SiroPHP M ai l reference
sidebar_position: 15
sidebar_label: M ai l
---

# Mail API Reference

## Overview

Siro's mail system supports sending via SMTP (with STARTTLS), sendmail, and log driver. Emails can be queued for async sending.

```php
use Siro\Core\Mail;
```

---

## Configuration

```env
MAIL_DRIVER=log              # log, smtp, sendmail
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Siro API"
MAIL_SSL_VERIFY=true         # false for dev (MITM risk)
```

---

## Sending Mail

### Simple Email

```php
Mail::to('user@example.com')
    ->subject('Welcome to Siro')
    ->html('<h1>Welcome!</h1><p>Thanks for joining.</p>')
    ->send();
```

### Using Mail Classes

Generate:

```bash
php siro make:mail WelcomeMail
```

Define:

```php
<?php

declare(strict_types=1);

namespace App\Mails;

use Siro\Core\Mail;

final class WelcomeMail extends Mail
{
    public function __construct(
        private readonly string $name,
        private readonly string $email,
    ) {}

    public function build(): void
    {
        $this->to($this->email)
            ->subject('Welcome, ' . $this->name)
            ->html($this->renderTemplate())
            ->attach('/path/to/guide.pdf');
    }

    private function renderTemplate(): string
    {
        return "<h1>Welcome {$this->name}!</h1><p>Thanks for joining Siro.</p>";
    }
}
```

Send:

```php
Mail::send(new WelcomeMail($user->name, $user->email));
```

---

## Attachments

```php
Mail::to('user@example.com')
    ->subject('Invoice')
    ->html('<p>Your invoice is attached.</p>')
    ->attach('/path/to/invoice.pdf')
    ->attach('/path/to/terms.pdf', ['name' => 'terms.pdf'])
    ->send();
```

---

## Async Queuing

```php
// Queue email (requires queue worker running)
Mail::to('user@example.com')
    ->subject('Welcome')
    ->html('<h1>Welcome</h1>')
    ->queue();

// Delay delivery
Mail::to('user@example.com')
    ->subject('Follow-up')
    ->html('<h1>How are you?</h1>')
    ->later(3600); // 1 hour later
```

Process queue:

```bash
php siro queue:work
```

---

## Mail Drivers

| Driver | Use Case | Config |
|--------|----------|--------|
| `log` | Development | Writes to `storage/logs/mail.log` |
| `smtp` | Production | SMTP server with optional STARTTLS |
| `sendmail` | Production | Local sendmail binary |

---

## Available Methods

| Method | Description |
|--------|-------------|
| `to(string $email)` | Set recipient |
| `subject(string $subject)` | Set subject |
| `html(string $content)` | Set HTML body |
| `text(string $content)` | Set plain text body |
| `attach(string $path, array $options)` | Attach file |
| `from(string $address, string $name)` | Override sender |
| `send()` | Send synchronously |
| `queue()` | Queue for async sending |
| `later(int $delay)` | Queue with delay |
| `cc(string $email)` | Add CC recipient |
| `bcc(string $email)` | Add BCC recipient |
| `replyTo(string $email)` | Set Reply-To header |
