import Skeleton from "@/components/ui/Skeleton";

export default function Loading() {
    return (
        <div className="min-h-screen bg-muslin-cream pt-32">
            <div className="max-w-7xl mx-auto px-4 md:px-6">
                <div className="space-y-12">
                    <div className="text-center space-y-4">
                        <Skeleton className="h-4 w-32 mx-auto" />
                        <Skeleton className="h-12 w-64 mx-auto" />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {[1, 2, 3].map((i) => (
                            <Skeleton key={i} className="aspect-square w-full rounded-2xl" />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
