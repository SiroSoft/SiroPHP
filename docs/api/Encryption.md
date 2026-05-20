# Encryption API Reference

## Overview

Siro provides AES-256 encryption with HKDF key separation and Encrypt-then-MAC authentication.

```php
use Siro\Core\Encrypter;
```

---

## Configuration

```env
APP_KEY=your-32-character-app-key-here
```

Generate a key:

```bash
php siro key:generate
```

---

## Basic Usage

### Encrypt

```php
$encrypted = Encrypter::encrypt('sensitive-data');
// Returns base64-encoded string with IV + ciphertext + MAC
```

### Decrypt

```php
$decrypted = Encrypter::decrypt($encrypted);
// Returns original plaintext string
```

---

## How It Works

### Encryption Flow

1. Generate random 16-byte IV (Initialization Vector)
2. Derive encryption key using HKDF-SHA256
3. Encrypt plaintext with AES-256-CBC
4. Compute HMAC-SHA256 over IV + ciphertext
5. Return base64(IV + ciphertext + MAC)

### Decryption Flow

1. Decode base64 input
2. Extract IV, ciphertext, MAC
3. Verify MAC using `hash_equals` (timing-safe)
4. Decrypt with AES-256-CBC
5. Return plaintext

---

## Key Separation

The master key (`APP_KEY`) is never used directly for encryption. Instead, HKDF derives separate keys:

| Purpose | Derived Key |
|---------|-------------|
| Encryption | `HKDF(APP_KEY, "encryption")` |
| Authentication | `HKDF(APP_KEY, "authentication")` |

This ensures that a vulnerability in one operation cannot compromise the other key.

---

## Security Properties

| Property | Implementation |
|----------|---------------|
| Confidentiality | AES-256-CBC |
| Integrity | HMAC-SHA256 (Encrypt-then-MAC) |
| Key Separation | HKDF-SHA256 |
| Timing Safety | `hash_equals()` for MAC verification |
| IV Randomness | `random_bytes()` (CSPRNG) |

---

## Best Practices

1. **Always use `APP_KEY` >= 32 characters** — shorter keys are rejected
2. **Keep `APP_KEY` secret** — never commit to version control
3. **Rotate keys periodically** — re-encrypt data with new key
4. **Use environment-specific keys** — different keys for dev/staging/production
5. **Never encrypt authentication tokens** — use JWT instead

---

## Available Methods

| Method | Description |
|--------|-------------|
| `encrypt(string $data, ?string $key)` | Encrypt data with optional custom key |
| `decrypt(string $payload, ?string $key)` | Decrypt payload with optional custom key |
| `generateIv()` | Generate random 16-byte IV |
| `hkdf(string $key, string $salt)` | Derive sub-key using HKDF-SHA256 |
