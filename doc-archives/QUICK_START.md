# Quick Start Guide

## 🐳 Push to Docker Hub

Simply run the script:

```bash
./docker-push.sh
```

This script will:
1. ✅ Check if Docker is running
2. ✅ Verify Docker Hub authentication (prompts login if needed)
3. ✅ Build the image: `sogeniusio/teamvault:latest`
4. ✅ Ask for confirmation before pushing
5. ✅ Push to Docker Hub

**Or manually:**
```bash
docker login
docker push sogeniusio/teamvault:latest
```

## 📦 Create GitHub Repository

### Option 1: GitHub CLI
```bash
gh repo create teamvault --private --source=. --remote=origin --push
```

### Option 2: Manual (GitHub Website)
1. Go to https://github.com/new
2. Repository name: `teamvault`
3. Set to **Private**
4. Don't initialize with README
5. Click "Create repository"
6. Then run:
```bash
git remote add origin https://github.com/hosquiat/teamvault.git
git push -u origin main
```

## ✅ What's Already Done

- ✅ Docker image built: `sogeniusio/teamvault:latest`
- ✅ Git repository initialized
- ✅ All code committed
- ✅ docker-push.sh script created
- ✅ Comprehensive README.md
- ✅ Docker configuration files
- ✅ Multi-stage Dockerfile optimized

## 🚀 After Pushing

### Deploy anywhere:
```bash
docker pull sogeniusio/teamvault:latest
docker-compose up -d
```

### Or build locally:
```bash
git clone https://github.com/hosquiat/teamvault.git
cd teamvault
docker-compose up -d
```

---

**Need more details?** See `DEPLOYMENT_STEPS.md` or `README.md`
