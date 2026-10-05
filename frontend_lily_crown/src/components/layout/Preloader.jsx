"use client";

import React, { useState, useEffect, useRef } from "react";
import { motion, AnimatePresence, useScroll, useTransform } from "framer-motion";
import Image from "next/image";

export function Preloader() {
    const [loading, setLoading] = useState(true);
    const [progress, setProgress] = useState(0);
    const [mounted, setMounted] = useState(false);
    const [isLoaded, setIsLoaded] = useState(false);
    const containerRef = useRef(null);

    useEffect(() => {
        setMounted(true);

        const handleLoad = () => setIsLoaded(true);
        if (document.readyState === "complete") {
            handleLoad();
        } else {
            window.addEventListener("load", handleLoad);
        }

        const progressInterval = setInterval(() => {
            setProgress((prev) => {
                if (isLoaded && prev >= 100) return 100;
                if (prev < 90) return prev + 0.8;
                if (isLoaded) return prev + 2;
                return prev;
            });
        }, 30);

        return () => {
            clearInterval(progressInterval);
            window.removeEventListener("load", handleLoad);
        };
    }, [isLoaded]);

    useEffect(() => {
        if (progress >= 100) {
            const timer = setTimeout(() => setLoading(false), 800);
            return () => clearTimeout(timer);
        }
    }, [progress]);

    const title = "LILY CROWN";

    // Gold Dust Particles
    const particles = Array.from({ length: 20 });

    return (
        <AnimatePresence mode="wait">
            {loading && (
                <motion.div
                    key="preloader-overlay"
                    initial={{ opacity: 1 }}
                    exit={{
                        opacity: 0,
                        scale: 1.1,
                        filter: "blur(40px)",
                        transition: { duration: 1.5, ease: [0.7, 0, 0.3, 1] }
                    }}
                    className="fixed inset-0 bg-[#080808] z-[99999] flex flex-col justify-center items-center overflow-hidden"
                >
                    {/* Cinematic Background Layers */}
                    <div className="absolute inset-0 pointer-events-none">
                        <div className="absolute inset-0 bg-radial-vignette opacity-40"></div>
                        <div className="absolute inset-0 bg-noise opacity-[0.08] mix-blend-screen"></div>

                        {/* Drifting Gold Dust */}
                        {mounted && particles.map((_, i) => (
                            <motion.div
                                key={`dust-${i}`}
                                initial={{
                                    x: Math.random() * 100 + "%",
                                    y: Math.random() * 100 + "%",
                                    opacity: 0
                                }}
                                animate={{
                                    y: ["-10%", "110%"],
                                    x: ["-5%", "5%"],
                                    opacity: [0, 0.4, 0],
                                    scale: [0, 1, 0]
                                }}
                                transition={{
                                    duration: 10 + Math.random() * 15,
                                    repeat: Infinity,
                                    delay: Math.random() * 10,
                                    ease: "linear"
                                }}
                                className="absolute w-[2px] h-[2px] bg-heritage-gold rounded-full blur-[1px]"
                            />
                        ))}
                    </div>

                    <div className="relative z-10 flex flex-col items-center max-w-lg w-full px-8" ref={containerRef}>
                        {/* Elite Logo Reveal */}
                        <motion.div
                            initial={{ opacity: 0, scale: 0.8, y: 20, filter: "blur(20px)" }}
                            animate={{ opacity: 1, scale: 1, y: 0, filter: "blur(0px)" }}
                            transition={{ duration: 2.2, ease: [0.16, 1, 0.3, 1] }}
                            className="relative w-40 h-40 md:w-56 md:h-56 mb-16"
                        >
                            {/* Inner Aura */}
                            <motion.div
                                animate={{ scale: [1, 1.1, 1], opacity: [0.1, 0.15, 0.1] }}
                                transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
                                className="absolute inset-0 bg-heritage-gold/20 blur-[80px] rounded-full"
                            />

                            <Image
                                src="/img/logo.png"
                                alt="Lily Crown"
                                fill
                                className="object-contain drop-shadow-[0_0_30px_rgba(153,101,21,0.2)]"
                                priority
                            />
                        </motion.div>

                        {/* Archival Title Animation */}
                        <div className="relative overflow-hidden mb-6">
                            <motion.h1
                                initial={{ letterSpacing: "1.5em", opacity: 0, filter: "blur(10px)", x: "0.75em" }}
                                animate={{ letterSpacing: "0.6em", opacity: 1, filter: "blur(0px)", x: "0.3em" }}
                                transition={{ delay: 0.5, duration: 2.5, ease: [0.16, 1, 0.3, 1] }}
                                className="font-serif text-3xl md:text-5xl text-heritage-gold font-medium tracking-[0.6em] whitespace-nowrap"
                            >
                                {title}
                            </motion.h1>
                        </div>

                        {/* Subtle Tagline */}
                        <motion.div
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 0.5, y: 0 }}
                            transition={{ delay: 1.8, duration: 1.5 }}
                            className="flex items-center gap-4 mb-16"
                        >
                            <div className="w-8 h-[0.5px] bg-heritage-gold/30" />
                            <p className="text-[10px] md:text-xs uppercase tracking-[0.8em] text-white/60 font-medium">
                                Heirlooms of Bengal
                            </p>
                            <div className="w-8 h-[0.5px] bg-heritage-gold/30" />
                        </motion.div>

                        {/* Perimeter Thread Loader */}
                        <div className="relative w-full h-[1px] bg-white/5 overflow-hidden">
                            <motion.div
                                initial={{ x: "-100%" }}
                                animate={{ x: `${progress - 100}%` }}
                                transition={{ duration: 0.4, ease: "easeOut" }}
                                className="absolute inset-0 bg-gradient-to-r from-transparent via-heritage-gold to-transparent opacity-80"
                            />
                            {/* Glow Tip */}
                            <motion.div
                                animate={{ left: `${progress}%` }}
                                className="absolute top-1/2 -translate-y-1/2 w-12 h-4 bg-heritage-gold/30 blur-md rounded-full"
                            />
                        </div>

                        {/* Descriptive Status */}
                        <motion.p
                            initial={{ opacity: 0 }}
                            animate={{ opacity: [0, 0.4, 0.2] }}
                            transition={{ delay: 2.2, duration: 3, repeat: Infinity, repeatType: "reverse" }}
                            className="mt-8 text-[8px] uppercase tracking-[0.6em] text-heritage-gold/50 font-bold"
                        >
                            Curating The Royal Collection
                        </motion.p>
                    </div>

                    {/* Elite Flash Effect on Mount */}
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: [0, 0.05, 0] }}
                        transition={{ duration: 3, repeat: Infinity }}
                        className="absolute inset-0 bg-white pointer-events-none z-20"
                    />
                </motion.div>
            )}
        </AnimatePresence>
    );
}
