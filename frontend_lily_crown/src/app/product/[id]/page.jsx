import { cache } from "react";
import { api } from "@/lib/api";
import { getOptimizedImage } from "@/lib/utils";
import { Navbar } from "@/components/layout/Navbar";
import { Footer } from "@/components/layout/Footer";
import { ProductDetail } from "@/components/product/ProductDetail";
import { RelatedProducts } from "@/components/product/RelatedProducts";
import { WishlistModal } from "@/components/ui/WishlistModal";
import { Toast } from "@/components/ui/Toast";
import { notFound } from "next/navigation";

// `id` may be a numeric id or a slug; cached so metadata and the page share one request
const getProduct = cache((idOrSlug) => api.getProduct(idOrSlug));

export async function generateMetadata({ params }) {
    const { id } = await params;
    const product = await getProduct(id);

    if (!product) {
        notFound();
    }

    const description = product.description?.slice(0, 160) || `Shop ${product.name} at Lily Crown.`;
    const image = getOptimizedImage(product.image);

    return {
        title: product.name,
        description,
        alternates: { canonical: `/product/${product.slug || product.id}` },
        openGraph: {
            title: product.name,
            description,
            images: [{ url: image, alt: product.name }],
        },
    };
}

export default async function ProductPage({ params }) {
    const { id } = await params;
    const product = await getProduct(id);

    if (!product) {
        notFound();
    }

    const related = await api.getRelatedProducts(product.id);

    return (
        <main className="min-h-screen">
            <Navbar />
            <ProductDetail product={product} />
            <RelatedProducts product={product} products={related} />
            <Footer />

            {/* Overlays */}
            <WishlistModal />
            <Toast />
        </main>
    );
}
