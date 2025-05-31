const fs = require('fs-extra');
const path = require('path');

const isWindows = process.platform === 'win32';
const separator = isWindows ? '\\' : '/';

const assets = {
    'bootstrap-css': {
        src: 'node_modules/bootstrap/dist/css/bootstrap.min.css',
        dest: 'assets/css/bootstrap.min.css'
    },
    'bootstrap-js': {
        src: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
        dest: 'assets/js/bootstrap.bundle.min.js'
    },
    'fontawesome-css': {
        src: 'node_modules/@fortawesome/fontawesome-free/css/all.min.css',
        dest: 'assets/css/all.min.css'
    },
    'fontawesome-webfonts': {
        src: 'node_modules/@fortawesome/fontawesome-free/webfonts',
        dest: 'assets/webfonts'
    },
    'poppins': {
        src: 'node_modules/@fontsource/poppins/files',
        dest: 'assets/fonts',
        files: [
            'poppins-latin-300-normal.woff2',
            'poppins-latin-400-normal.woff2',
            'poppins-latin-500-normal.woff2',
            'poppins-latin-600-normal.woff2',
            'poppins-latin-700-normal.woff2'
        ]
    }
};

async function copyAsset(key) {
    const asset = assets[key];
    if (!asset) {
        console.error(`Asset ${key} not found`);
        return;
    }

    try {
        if (asset.files) {
            // Copie de plusieurs fichiers
            for (const file of asset.files) {
                const srcPath = path.join(process.cwd(), asset.src, file);
                const destPath = path.join(process.cwd(), asset.dest, file);
                await fs.copy(srcPath, destPath);
                console.log(`Copied ${file} to ${asset.dest}`);
            }
        } else {
            // Copie d'un seul fichier ou dossier
            const srcPath = path.join(process.cwd(), asset.src);
            const destPath = path.join(process.cwd(), asset.dest);
            await fs.copy(srcPath, destPath);
            console.log(`Copied ${asset.src} to ${asset.dest}`);
        }
    } catch (err) {
        console.error(`Error copying ${key}:`, err);
    }
}

async function copyAll() {
    for (const key of Object.keys(assets)) {
        await copyAsset(key);
    }
}

const assetKey = process.argv[2];
if (assetKey === 'all') {
    copyAll();
} else {
    copyAsset(assetKey);
} 