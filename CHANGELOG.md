# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-02-11

### Added - AI Enhancement Phase
- 🎙️ AI Voice Commands (Hands-Free) with Voice Activity Detection
- 💊 Smart Drug Label Generation with visual time icons (☀️🍛🌙)
- 📊 Visual Analytics with interactive Chart.js graphs
- ⏰ Refill Reminders for proactive patient care (25-35 days)
- 🏬 Cross-Branch Stock Check with color-coded display
- 🛒 Instant Purchase Order via AI chat
- 🧠 AI-Driven Intelligence features:
  - ADR (Adverse Drug Reaction) Surveillance
  - Drug Interaction Detection
  - Clinical Decision Support
  - Patient Safety Reporting

### Fixed
- 🐛 CSRF validation issues in order updates
- 🐛 SQL errors in patient usage tracking queries
- 🐛 Analytics calculation bugs (Dead Stock, Revenue)
- 🐛 PDO parameter binding errors

### Improved
- ⚡ Performance optimization with better caching
- 🔧 Enhanced AI response accuracy
- 📱 Better responsive design for mobile devices

## [1.4.0] - 2026-02-06

### Added - Telepharmacy & Patient Engagement
- 📞 Telepharmacy module with Jitsi Meet integration
- 👥 Patient Engagement Portal
- 💡 AI-driven health advice
- 📝 Clinical notes with AI analysis
- 🔍 ADR surveillance system

### Improved
- 🔗 Enhanced JHCIS integration
- 📊 Better patient data visualization

## [1.3.0] - 2026-01-30

### Added - JHCIS Integration Enhancement
- 🏥 Multi-hospital JHCIS support
- 🔄 Auto-mapping and reconciliation
- 🧹 Data cleansing tools
- 📈 Executive summary reports

### Improved
- ⚡ Improved data synchronization performance
- 🔧 Better error handling for JHCIS connections

## [1.2.0] - 2026-01-27

### Added - LINE Notification & Forecasting
- 📱 LINE Notify integration
- 🔮 Demand forecasting (Prophet/LSTM models)
- 🔔 Automated alerts and notifications
- ⏰ Scheduled daily summaries

### Improved
- 📊 Enhanced reporting capabilities
- 🎯 Better notification targeting

## [1.1.0] - 2026-01-21

### Added - JHCIS Data Integration
- 🧪 Lab results integration
- 💉 Vaccination history
- 🩺 Screening data
- 🏥 Chronic disease management

### Improved
- 👤 Enhanced patient profiles with comprehensive data
- 🔗 Better JHCIS data mapping

## [1.0.0] - 2026-01-15

### Added - Initial Release
- 🎉 Core Inventory Management
  - FEFO (First Expire, First Out) system
  - Multi-warehouse support
  - Real-time stock tracking
  - Expiry date monitoring
- 🔗 Basic JHCIS Integration
- 💊 Dispensing Module
- 🛒 Order Management
- 📊 Basic Reporting
- 👥 User Management
- 🔐 Security features (CSRF, XSS, SQL Injection protection)

---

## Version Naming Convention

- **Major.Minor.Patch** (e.g., 1.5.0)
- **Major**: Breaking changes or major new features
- **Minor**: New features, backward compatible
- **Patch**: Bug fixes and minor improvements

## Types of Changes

- **Added**: New features
- **Changed**: Changes in existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Security improvements
