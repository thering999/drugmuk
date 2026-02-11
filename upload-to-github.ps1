# Drugmuk - GitHub Upload Script (PowerShell)
# This script helps you upload all code to GitHub

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  Drugmuk - GitHub Upload Script" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Check if git is installed
try {
    git --version | Out-Null
} catch {
    Write-Host "Error: Git is not installed!" -ForegroundColor Red
    Write-Host "Please install Git first: https://git-scm.com/downloads"
    exit 1
}

# Check if we're in a git repository
if (-not (Test-Path .git)) {
    Write-Host "Initializing Git repository..." -ForegroundColor Yellow
    git init
    Write-Host "✓ Git repository initialized" -ForegroundColor Green
}

# Check for remote
$hasRemote = git remote | Select-String -Pattern "origin"
if (-not $hasRemote) {
    Write-Host "Adding GitHub remote..." -ForegroundColor Yellow
    $repoUrl = Read-Host "Enter your GitHub repository URL"
    git remote add origin $repoUrl
    Write-Host "✓ Remote added" -ForegroundColor Green
}

# Show current status
Write-Host ""
Write-Host "Current Git Status:" -ForegroundColor Yellow
git status --short

# Ask for confirmation
Write-Host ""
$addAll = Read-Host "Do you want to add all files? (y/n)"

if ($addAll -eq "y" -or $addAll -eq "Y") {
    Write-Host "Adding all files..." -ForegroundColor Yellow
    git add .
    Write-Host "✓ Files added" -ForegroundColor Green
}

# Show what will be committed
Write-Host ""
Write-Host "Files to be committed:" -ForegroundColor Yellow
git status --short

# Ask for commit message
Write-Host ""
$commitMsg = Read-Host "Enter commit message (or press Enter for default)"

if ([string]::IsNullOrWhiteSpace($commitMsg)) {
    $commitMsg = @"
feat: Update comprehensive README and documentation

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
"@
}

# Commit
Write-Host ""
Write-Host "Committing changes..." -ForegroundColor Yellow
git commit -m $commitMsg

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Changes committed" -ForegroundColor Green
} else {
    Write-Host "✗ Commit failed" -ForegroundColor Red
    exit 1
}

# Ask for branch
Write-Host ""
$branch = Read-Host "Enter branch name (default: main)"
if ([string]::IsNullOrWhiteSpace($branch)) {
    $branch = "main"
}

# Check if branch exists
$branchExists = git show-ref --verify --quiet refs/heads/$branch
if ($LASTEXITCODE -ne 0) {
    Write-Host "Creating branch $branch..." -ForegroundColor Yellow
    git branch -M $branch
}

# Push to GitHub
Write-Host ""
$doPush = Read-Host "Push to GitHub? (y/n)"

if ($doPush -eq "y" -or $doPush -eq "Y") {
    Write-Host "Pushing to GitHub..." -ForegroundColor Yellow
    git push -u origin $branch
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "=========================================" -ForegroundColor Green
        Write-Host "  ✓ Successfully uploaded to GitHub!" -ForegroundColor Green
        Write-Host "=========================================" -ForegroundColor Green
        Write-Host ""
        $remoteUrl = git remote get-url origin
        Write-Host "Repository: $remoteUrl"
        Write-Host "Branch: $branch"
        Write-Host ""
    } else {
        Write-Host "✗ Push failed" -ForegroundColor Red
        Write-Host "Please check your credentials and try again"
        exit 1
    }
} else {
    Write-Host "Skipped push to GitHub" -ForegroundColor Yellow
    Write-Host "You can push manually later with: git push -u origin $branch"
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green
