# คู่มือการอัปโหลดโค้ดไปยัง GitHub

## ขั้นตอนการอัปโหลดครั้งแรก

### 1. ตรวจสอบสถานะ Git

```bash
cd d:\www\drugmuk
git status
```

### 2. เพิ่มไฟล์ทั้งหมดเข้า Git

```bash
git add .
```

### 3. Commit การเปลี่ยนแปลง

```bash
git commit -m "feat: Update comprehensive README and documentation

- ✨ Created detailed README.md with complete documentation
- 📝 Added CHANGELOG.md with version history
- 🤝 Added CONTRIBUTING.md with contribution guidelines
- 📄 Added LICENSE (MIT License)
- 🔧 Updated .gitignore for better file management
- 📁 Added .gitkeep files for directory structure
- 🚀 Ready for production deployment

This update includes:
- Complete installation guide
- AI Assistant documentation
- API documentation
- Database schema details
- Security features overview
- Deployment checklist
- Roadmap for future versions
"
```

### 4. ตรวจสอบ Remote Repository

```bash
git remote -v
```

ถ้ายังไม่มี remote ให้เพิ่ม:

```bash
git remote add origin https://github.com/thering999/drugmuk.git
```

### 5. Push ไปยัง GitHub

```bash
# Push ไปยัง main branch
git push -u origin main
```

หรือถ้าใช้ master branch:

```bash
git push -u origin master
```

---

## การอัปเดทครั้งต่อไป

### 1. ตรวจสอบการเปลี่ยนแปลง

```bash
git status
```

### 2. เพิ่มไฟล์ที่เปลี่ยนแปลง

```bash
# เพิ่มทั้งหมด
git add .

# หรือเพิ่มเฉพาะไฟล์
git add path/to/file
```

### 3. Commit

```bash
git commit -m "ข้อความอธิบายการเปลี่ยนแปลง"
```

### 4. Push

```bash
git push
```

---

## รูปแบบ Commit Message

### ใช้ Conventional Commits

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types
- `feat`: ฟีเจอร์ใหม่
- `fix`: แก้ไข bug
- `docs`: เปลี่ยนแปลง documentation
- `style`: การจัดรูปแบบโค้ด (ไม่เปลี่ยนการทำงาน)
- `refactor`: ปรับปรุงโค้ด
- `perf`: ปรับปรุงประสิทธิภาพ
- `test`: เพิ่มหรือแก้ไข tests
- `chore`: งานบำรุงรักษา

### ตัวอย่าง

```bash
# ฟีเจอร์ใหม่
git commit -m "feat(ai): Add voice command feature with Thai language support"

# แก้ไข bug
git commit -m "fix(csrf): Fix CSRF validation in order updates"

# อัปเดท documentation
git commit -m "docs(readme): Update installation guide with Docker instructions"

# ปรับปรุงโค้ด
git commit -m "refactor(services): Improve AiService performance with caching"
```

---

## การจัดการ Branch

### สร้าง Branch ใหม่

```bash
# สร้างและเปลี่ยนไปยัง branch ใหม่
git checkout -b feature/new-feature

# หรือใช้คำสั่งใหม่
git switch -c feature/new-feature
```

### เปลี่ยน Branch

```bash
git checkout main
# หรือ
git switch main
```

### Merge Branch

```bash
# เปลี่ยนไปยัง main branch
git checkout main

# Merge feature branch
git merge feature/new-feature

# Push
git push
```

### ลบ Branch

```bash
# ลบ local branch
git branch -d feature/new-feature

# ลบ remote branch
git push origin --delete feature/new-feature
```

---

## การแก้ไขปัญหาที่พบบ่อย

### 1. ลืม pull ก่อน push

```bash
git pull origin main
# แก้ไข conflicts (ถ้ามี)
git push
```

### 2. Commit ผิด

```bash
# แก้ไข commit ล่าสุด
git commit --amend -m "ข้อความใหม่"

# Undo commit ล่าสุด (เก็บการเปลี่ยนแปลง)
git reset --soft HEAD~1

# Undo commit ล่าสุด (ลบการเปลี่ยนแปลง)
git reset --hard HEAD~1
```

### 3. ต้องการยกเลิกการเปลี่ยนแปลง

```bash
# ยกเลิกการเปลี่ยนแปลงในไฟล์
git checkout -- path/to/file

# ยกเลิกทุกอย่าง
git reset --hard HEAD
```

### 4. ดู commit history

```bash
# แบบสั้น
git log --oneline

# แบบละเอียด
git log

# แบบกราฟ
git log --graph --oneline --all
```

---

## การใช้ .gitignore

ไฟล์ที่ควร ignore:

```gitignore
# Sensitive files
.env
.env.local

# Dependencies
vendor/

# Logs
logs/*.log

# Temporary files
storage/cache/*
storage/sessions/*
storage/temp/*

# IDE
.vscode/
.idea/
```

---

## GitHub Actions (CI/CD) - Optional

สร้างไฟล์ `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: composer test
```

---

## การสร้าง Release

### 1. สร้าง Tag

```bash
git tag -a v1.5.0 -m "Release version 1.5.0 - AI Enhancement Phase"
git push origin v1.5.0
```

### 2. สร้าง Release บน GitHub

1. ไปที่ https://github.com/thering999/drugmuk/releases
2. คลิก "Create a new release"
3. เลือก tag v1.5.0
4. เขียน Release notes
5. คลิก "Publish release"

---

## คำสั่งที่ใช้บ่อย

```bash
# ดูสถานะ
git status

# ดู diff
git diff

# ดู commit history
git log --oneline

# Pull ล่าสุด
git pull

# Push
git push

# Clone repository
git clone https://github.com/thering999/drugmuk.git

# ดู remote
git remote -v

# ดู branch ทั้งหมด
git branch -a
```

---

## เคล็ดลับ

1. **Commit บ่อยๆ** - แต่ละ commit ควรมีความหมายชัดเจน
2. **Pull ก่อน Push** - เพื่อหลีกเลี่ยง conflicts
3. **ใช้ Branch** - สำหรับฟีเจอร์ใหม่หรือการแก้ไข
4. **เขียน Commit Message ที่ดี** - อธิบายว่าทำอะไรและทำไม
5. **Review โค้ดก่อน Commit** - ใช้ `git diff` ตรวจสอบ
6. **ใช้ .gitignore** - อย่า commit ไฟล์ที่ไม่จำเป็น

---

## ทรัพยากรเพิ่มเติม

- [Git Documentation](https://git-scm.com/doc)
- [GitHub Guides](https://guides.github.com/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Git Cheat Sheet](https://education.github.com/git-cheat-sheet-education.pdf)

---

**สร้างโดย**: Drugmuk Development Team  
**อัปเดทล่าสุด**: 2026-02-11
