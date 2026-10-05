#!/bin/bash
# Script to update all component imports to new structure

# Update all files in app directory
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/Navbar"|from "@/components/layout/Navbar"|g' {} +
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/Footer"|from "@/components/layout/Footer"|g' {} +
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/ProductCard"|from "@/components/product/ProductCard"|g' {} +
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/ProductDetail"|from "@/components/product/ProductDetail"|g' {} +
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/RelatedProducts"|from "@/components/product/RelatedProducts"|g' {} +
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/SectionHeader"|from "@/components/ui/SectionHeader"|g' {} +
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/WishlistModal"|from "@/components/ui/WishlistModal"|g' {} +
find src/app -type f -name "*.jsx" -exec sed -i 's|from "@/components/Toast"|from "@/components/ui/Toast"|g' {} +

echo "Import paths updated successfully!"
