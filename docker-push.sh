#!/bin/bash

# Docker Push Script for ESMSPASS
# This script builds and pushes the Docker image to Docker Hub

set -e  # Exit on error

DOCKER_REPO="sogeniusio/esmspass"
TAG="latest"
IMAGE="${DOCKER_REPO}:${TAG}"

echo "========================================="
echo "  ESMSPASS Docker Build & Push Script"
echo "========================================="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running"
    echo "Please start Docker Desktop and try again"
    exit 1
fi

# Check if logged in to Docker Hub
echo "📋 Checking Docker Hub authentication..."
if ! docker info 2>/dev/null | grep -q "Username"; then
    echo ""
    echo "⚠️  You are not logged in to Docker Hub"
    echo "Please login with your Docker Hub credentials:"
    echo ""
    docker login
    if [ $? -ne 0 ]; then
        echo "❌ Login failed. Exiting."
        exit 1
    fi
fi

echo "✅ Docker Hub authentication confirmed"
echo ""

# Build the image
echo "🔨 Building Docker image: ${IMAGE}"
echo "This may take several minutes..."
echo ""

if docker build -t "${IMAGE}" .; then
    echo ""
    echo "✅ Build successful!"
else
    echo ""
    echo "❌ Build failed. Please check the errors above."
    exit 1
fi

echo ""

# Show image info
echo "📦 Image details:"
docker images "${DOCKER_REPO}" --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"
echo ""

# Ask for confirmation before pushing
read -p "🚀 Push ${IMAGE} to Docker Hub? (y/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "📤 Pushing image to Docker Hub..."
    echo "This may take several minutes depending on your connection..."
    echo ""

    if docker push "${IMAGE}"; then
        echo ""
        echo "========================================="
        echo "  ✅ SUCCESS!"
        echo "========================================="
        echo ""
        echo "Image pushed to: ${IMAGE}"
        echo "Docker Hub URL: https://hub.docker.com/r/${DOCKER_REPO}"
        echo ""
        echo "Anyone can now pull your image with:"
        echo "  docker pull ${IMAGE}"
        echo ""
    else
        echo ""
        echo "❌ Push failed. Please check the errors above."
        exit 1
    fi
else
    echo ""
    echo "❌ Push cancelled by user"
    echo ""
    echo "To push manually later, run:"
    echo "  docker push ${IMAGE}"
    echo ""
fi
