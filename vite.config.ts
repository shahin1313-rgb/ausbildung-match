import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
  plugins: [react()],
  publicDir: false,
  server: {
    host: "127.0.0.1",
    port: 5173,
    strictPort: true,
    cors: true,
  },
  build: {
    outDir: "public/build",
    emptyOutDir: true,
    cssCodeSplit: false,
    rollupOptions: {
      input: "resources/js/main.tsx",
      output: {
        entryFileNames: "assets/app.js",
        chunkFileNames: "assets/[name]-[hash].js",
        assetFileNames: (assetInfo) =>
          assetInfo.name?.endsWith(".css")
            ? "assets/app.css"
            : "assets/[name]-[hash][extname]",
      },
    },
  },
});
