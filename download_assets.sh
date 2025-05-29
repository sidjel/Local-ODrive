#!/bin/bash

# Création des dossiers nécessaires
mkdir -p assets/css
mkdir -p assets/js
mkdir -p assets/fonts
mkdir -p assets/webfonts

# Téléchargement de Bootstrap CSS
curl -o assets/css/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css

# Téléchargement de Bootstrap JS
curl -o assets/js/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js

# Téléchargement de Font Awesome
curl -L -o fontawesome.zip https://use.fontawesome.com/releases/v6.0.0/fontawesome-free-6.0.0-web.zip
unzip fontawesome.zip
mv fontawesome-free-6.0.0-web/css/all.min.css assets/css/
mv fontawesome-free-6.0.0-web/webfonts/* assets/webfonts/
rm -rf fontawesome-free-6.0.0-web fontawesome.zip

# Téléchargement de Poppins (Google Fonts)
curl -o assets/fonts/poppins-v20-latin-300.woff2 https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLDz8Z1xlFQ.woff2
curl -o assets/fonts/poppins-v20-latin-400.woff2 https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8JHgFVrJJfecg.woff2
curl -o assets/fonts/poppins-v20-latin-500.woff2 https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLGT9Z1xlFQ.woff2
curl -o assets/fonts/poppins-v20-latin-600.woff2 https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLEj6Z1xlFQ.woff2
curl -o assets/fonts/poppins-v20-latin-700.woff2 https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLCz7Z1xlFQ.woff2

# Création du fichier CSS pour les polices Poppins
cat > assets/css/poppins.css << 'EOL'
/* poppins-300 - latin */
@font-face {
  font-family: 'Poppins';
  font-style: normal;
  font-weight: 300;
  src: url('../fonts/poppins-v20-latin-300.woff2') format('woff2');
}

/* poppins-regular - latin */
@font-face {
  font-family: 'Poppins';
  font-style: normal;
  font-weight: 400;
  src: url('../fonts/poppins-v20-latin-400.woff2') format('woff2');
}

/* poppins-500 - latin */
@font-face {
  font-family: 'Poppins';
  font-style: normal;
  font-weight: 500;
  src: url('../fonts/poppins-v20-latin-500.woff2') format('woff2');
}

/* poppins-600 - latin */
@font-face {
  font-family: 'Poppins';
  font-style: normal;
  font-weight: 600;
  src: url('../fonts/poppins-v20-latin-600.woff2') format('woff2');
}

/* poppins-700 - latin */
@font-face {
  font-family: 'Poppins';
  font-style: normal;
  font-weight: 700;
  src: url('../fonts/poppins-v20-latin-700.woff2') format('woff2');
}
EOL

echo "Téléchargement terminé !" 