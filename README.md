# 📬 XKCD Comic Email Subscription System

A full-featured PHP project to manage email subscriptions and send daily XKCD comics directly to your inbox with verification and unsubscribe functionality.

---

## 📌 Features

### 1️⃣ Email Verification
- 📥 Users enter their email to subscribe.
- 🔐 A secure 6-digit code is sent to their inbox.
- ✅ Users verify their email using the code.
- 💾 Verified emails are stored in `registered_emails.txt`.

---

### 2️⃣ Unsubscribe Mechanism
- 📩 All emails contain an **unsubscribe** link.
- 🧾 Users are taken to the unsubscribe page.
- 🔐 A 6-digit code is sent to their email for confirmation.
- ❌ On verification, the user is removed from the subscribers list.

---

### 3️⃣ XKCD Comic Subscription
- ⏰ Every 24 hours (via CRON job):
  - 📡 Fetches a random comic from [xkcd.com](https://xkcd.com).
  - 🖼️ Formats the comic into clean HTML.
  - 📬 Emails the comic to all registered subscribers.

---

## 🚀 Getting Started

### 🔧 Prerequisites
- ✅ [XAMPP](https://www.apachefriends.org/index.html) (Apache + MySQL)
- ✅ PHP installed and configured

---
## 🎥 Demo Video

[![Watch the demo](https://img.youtube.com/vi/e018NNg7PWs/0.jpg)](https://youtu.be/e018NNg7PWs)



