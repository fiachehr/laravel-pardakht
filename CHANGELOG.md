# Changelog

All notable changes to this project will be documented in this file.

## [1.0.8] - 2026-09-27

### Fixed
- Mabna (Sepehr) now calls the 4.0.2 REST endpoints on port 443. Token, payment, and advice no longer use the retired `:8081` host whose certificate cURL rejects.
- Advice sends `InvoiceID` and treats `Status` as `OK`, `NOK`, or `Duplicate`. The returned amount is compared with the requested amount.

## [1.0.0] - 2024-10-09

### Added
- Support for Mellat Bank gateway (Bank-e Mellat)
- Support for Mabna Card gateway (Sepehr)
- Support for ZarinPal gateway
- Complete SOLID architecture
- Design Patterns implementation: Repository, Contract, Factory, Facade
- Value Objects for requests and responses
- Automatic transaction storage system
- Professional error handling
- Sandbox Mode capability
- Complete documentation
- Migration for transactions table
- Repository Pattern for transaction management
- Ability to extend with custom gateways

### Architecture Features
- Single Responsibility Principle (SRP)
- Open/Closed Principle (OCP)
- Liskov Substitution Principle (LSP)
- Interface Segregation Principle (ISP)
- Dependency Inversion Principle (DIP)

[1.0.0]: https://github.com/fiachehr/laravel-pardakht/releases/tag/v1.0.0
