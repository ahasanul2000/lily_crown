"use client";

import React from "react";
import { motion } from "framer-motion";

export function SectionHeader({ title, subtitle }) {
    return (
        <div className="bg-muslin-cream py-20 px-4 md:px-6 text-center border-b border-heritage-gold/5">
            <motion.span
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="text-heritage-gold text-xs uppercase tracking-[0.3em] font-bold mb-4 block"
            >
                {subtitle}
            </motion.span>
            <motion.h1
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="text-4xl md:text-6xl font-serif text-emerald-royal"
            >
                {title}
            </motion.h1>
            <div className="w-16 h-px bg-heritage-gold/30 mx-auto mt-8"></div>
        </div>
    );
}
