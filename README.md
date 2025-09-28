# qris-dynamicify

**qris-dynamicify** is a powerful and lightweight `composer` library for effortlessly transforming static QRIS (Quick Response Code Indonesian Standard) into dynamic QRIS. This tool is designed for developers and businesses in Indonesia who need to generate transaction-specific QR codes with dynamic amounts, streamlining the payment process for modern applications.

The project solves the problem of static, non-specific QRIS codes by allowing you to programmatically inject transaction details like price and taxes, making the payment experience seamless for both merchants and customers.

## Key Features

- **Static to Dynamic Conversion**: Convert any valid static QRIS string or image into a dynamic one.
- **Set Transaction Amount**: Easily set a specific price for the transaction.
- **Add Tax/Service Fee**: Include nominal or percentage-based taxes and fees.
- **QRIS Metadata Extraction**: Parse and retrieve merchant information from any QRIS code.
- **Multiple Input Formats**: Accepts QRIS data from a raw string or an image file (`.png`, `.jpg`, `.jpeg`).
- **Flexible Output**: Generate a dynamic QRIS as a raw string or save it directly as a QR code image.
- **PHP-Friendly**: Fully written in PHP with type-safe methods for a clean development experience.
- **Lightweight**: Minimal dependencies to keep your project lean.

## Installation

You can install `qris-dynamicify` via composer.

```bash
composer require qris-dynamicify
```

## Usage Instructions

Here’s how to get started with `qris-dynamicify`.

### Basic Example: From a Static QRIS String

```php
require_once 'vendor/autoload.php';

use XanderID\QrisDynamicify\QrisDynamicify;
use XanderID\QrisDynamicify\QrisException;

try {
    $staticQris = "00020101021126590013ID.CO.GOPAY.WWW021500000000000000000303UKE51450014ID.CO.GOPAY.MERCHANT02151234567890123450303UKE5204581253033605802ID5917MERCHANT NATIONAL6011JAKARTA SEL61051215062070703A01";

    $dynamicQris = QrisDynamicify::fromString($staticQris);

    // Set price and tax
    $dynamicQris->setPrice(50000)->setTax("10%");

    // Get the dynamic QRIS string
    echo "Dynamic QRIS String: " . $dynamicQris . PHP_EOL;

    // Save as PNG file
    $dynamicQris->writeToFile("dynamic-qris.png");
    echo "Successfully generated dynamic-qris.png" . PHP_EOL;

} catch (QrisException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

```

### Example: From a QRIS Image File

```php
require_once 'vendor/autoload.php';

use XanderID\QrisDynamicify\QrisDynamicify;
use XanderID\QrisDynamicify\QrisException;

try {
    $dynamicQrisFromFile = QrisDynamicify::fromFile("path/to/static-qris.png");

    // Set price and fixed tax
    $dynamicQrisFromFile->setPrice(125000)->setTax(5000);

    // Save new QRIS file
    $dynamicQrisFromFile->writeToFile("new-dynamic-qris.png");
    echo "Successfully generated new-dynamic-qris.png from file" . PHP_EOL;

} catch (QrisException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
```

## API Documentation

The main entry points are `fromString` and `fromFile`, which return a `QrisDynamicify` instance.

---

### `fromString(string $staticQris): QrisDynamicify`

- **Description**: Creates a `QrisDynamicify` instance from a static QRIS string.  
- **Parameters**:
  - `$staticQris` (string): The raw static QRIS data.  
- **Returns**: A `QrisDynamicify` instance.  
- **Throws**: `QrisException` if the string is empty.  

---

### `fromFile(string $filePath): QrisDynamicify`

- **Description**: Creates a `QrisDynamicify` instance from an image file containing a static QRIS.  
- **Parameters**:
  - `$filePath` (string): Path to the QRIS image file (`.png`, `.jpg`, `.jpeg`).  
- **Returns**: A `QrisDynamicify` instance.  
- **Throws**: `QrisException` if the file cannot be read or is invalid.  

---

### `QrisDynamicify` Methods

#### `setPrice(int $price): self`

- **Description**: Sets the transaction amount.  
- **Parameters**:
  - `$price` (int): The amount in your currency (e.g., IDR). Must be ≥ 0.  
- **Returns**: The same `QrisDynamicify` instance for method chaining.  
- **Throws**: `QrisException` if the price is negative.  

---

#### `setTax(int|string $tax): self`

- **Description**: Sets the tax or service fee. Can accept a fixed number or a percentage string.  
- **Parameters**:
  - `$tax` (int|string): Tax amount. Examples:
    - Fixed nominal: `5000`  
    - Percentage: `"10%"`  
- **Returns**: The same `QrisDynamicify` instance for method chaining.  
- **Throws**: `QrisException` if the value is negative or format is invalid.  

---

### `getMetadata(): QrisMetadata`

- **Description**: Extracts and returns metadata from the QRIS code.  
- **Returns**: A `QrisMetadata` object containing the following getter methods:

#### `QrisMetadata` Getters

| Method                              | Description                                        |
| ----------------------------------- | -------------------------------------------------- |
| `getMerchant(): string`             | Returns the merchant name.                         |
| `getCompany(): string`              | Returns the company or organization name.          |
| `getRegion(): string`               | Returns the region or province.                    |
| `getCountry(): string`              | Returns the country code (ISO).                    |
| `getPostalCode(): string`           | Returns the postal/ZIP code.                       |
| `getMerchantPan(): string`          | Returns the merchant PAN (Primary Account Number). |
| `getPrice(): ?string`               | Returns the transaction price, if available.       |
| `getTax(): ?string`                 | Returns the tax amount, if available.              |
| `toArray(): array<string, ?string>` | Returns all metadata as an associative array.      |

- **Throws**: `QrisException` if parsing fails.  

---

#### `writeToFile(string $filePath, ?QROptions $options = null): string`

- **Description**: Saves the current dynamic QRIS to a file.  
- **Parameters**:
  - `$filePath` (string): Destination path. Supports `.png`, `.jpg`, `.jpeg`, `.txt`.  
  - `$options` (`QROptions|null`): Optional QR code generation settings (size, error correction).  
- **Returns**: The saved file path.  
- **Throws**: `QrisException` if the file extension is unsupported.  

---

#### `__toString(): string`

- **Description**: Returns the raw dynamic QRIS string.  
- **Returns**: The dynamic QRIS string.

## Contribution Guide

We welcome contributions! Please follow these steps:

1. **Fork the repository**: Click the 'Fork' button at the top right of this page.
2. **Clone your fork**:

   ```bash
   git clone https://github.com/YOUR_USERNAME/qris-dynamicify-php.git
   ```

3. **Create a new branch**:

   ```bash
   git checkout -b feature/your-feature-name
   ```

4. **Make your changes**: Implement your feature or fix the bug.
5. **Commit your changes**:

   ```bash
   git commit -m "feat: Add some amazing feature"
   ```

6. **Push to your branch**:

   ```bash
   git push origin feature/your-feature-name
   ```

7. **Create a Pull Request**: Open a pull request from your fork to the main repository.

## Roadmap

- [ ] CLI tool for quick conversions from the terminal.
- [ ] Add more examples and detailed documentation.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

This project relies on the following open-source libraries:

- [php-qrcode](https://github.com/chillerlan/php-qrcode)

## FAQ

**Q: What is the difference between static and dynamic QRIS?**
**A:** A static QRIS contains only the merchant's information. The customer has to manually input the transaction amount. A dynamic QRIS is generated for a single transaction and includes the amount, making the payment process faster and less error-prone.

**Q: Can I use this for any QRIS code from any provider?**
**A:** Yes, this library should work with any valid QRIS code that complies with the Bank Indonesia standard.

## Why this project?

In the rapidly growing digital economy of Indonesia, QRIS has become a ubiquitous payment method. However, many smaller businesses still rely on static QRIS displays, which require manual entry of payment amounts. This can be slow and prone to errors. `qris-dynamicify` was created to bridge this gap, providing developers with a simple, powerful tool to integrate dynamic QRIS generation into their applications. Whether you're building a POS system, an e-commerce platform, or a donation portal, this library helps you create a smoother, more professional payment experience.
