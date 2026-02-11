#!/bin/bash

# Drugmuk - GitHub Upload Script
# This script helps you upload all code to GitHub

echo "========================================="
echo "  Drugmuk - GitHub Upload Script"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo -e "${RED}Error: Git is not installed!${NC}"
    echo "Please install Git first: https://git-scm.com/downloads"
    exit 1
fi

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo -e "${YELLOW}Initializing Git repository...${NC}"
    git init
    echo -e "${GREEN}✓ Git repository initialized${NC}"
fi

# Check for remote
if ! git remote | grep -q origin; then
    echo -e "${YELLOW}Adding GitHub remote...${NC}"
    read -p "Enter your GitHub repository URL: " repo_url
    git remote add origin "$repo_url"
    echo -e "${GREEN}✓ Remote added${NC}"
fi

# Show current status
echo ""
echo -e "${YELLOW}Current Git Status:${NC}"
git status --short

# Ask for confirmation
echo ""
read -p "Do you want to add all files? (y/n): " add_all

if [ "$add_all" = "y" ] || [ "$add_all" = "Y" ]; then
    echo -e "${YELLOW}Adding all files...${NC}"
    git add .
    echo -e "${GREEN}✓ Files added${NC}"
fi

# Show what will be committed
echo ""
echo -e "${YELLOW}Files to be committed:${NC}"
git status --short

# Ask for commit message
echo ""
read -p "Enter commit message (or press Enter for default): " commit_msg

if [ -z "$commit_msg" ]; then
    commit_msg="feat: Update comprehensive README and documentation

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
- Roadmap for future versions"
fi

# Commit
echo ""
echo -e "${YELLOW}Committing changes...${NC}"
git commit -m "$commit_msg"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Changes committed${NC}"
else
    echo -e "${RED}✗ Commit failed${NC}"
    exit 1
fi

# Ask for branch
echo ""
read -p "Enter branch name (default: main): " branch
branch=${branch:-main}

# Check if branch exists
if ! git show-ref --verify --quiet refs/heads/$branch; then
    echo -e "${YELLOW}Creating branch $branch...${NC}"
    git branch -M $branch
fi

# Push to GitHub
echo ""
read -p "Push to GitHub? (y/n): " do_push

if [ "$do_push" = "y" ] || [ "$do_push" = "Y" ]; then
    echo -e "${YELLOW}Pushing to GitHub...${NC}"
    git push -u origin $branch
    
    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}=========================================${NC}"
        echo -e "${GREEN}  ✓ Successfully uploaded to GitHub!${NC}"
        echo -e "${GREEN}=========================================${NC}"
        echo ""
        echo "Repository: $(git remote get-url origin)"
        echo "Branch: $branch"
        echo ""
    else
        echo -e "${RED}✗ Push failed${NC}"
        echo "Please check your credentials and try again"
        exit 1
    fi
else
    echo -e "${YELLOW}Skipped push to GitHub${NC}"
    echo "You can push manually later with: git push -u origin $branch"
fi

echo ""
echo -e "${GREEN}Done!${NC}"
