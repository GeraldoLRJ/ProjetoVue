# Simple Vue 2 dev server image
FROM node:18-alpine

WORKDIR /app

# Install only once; leverage docker layer cache
COPY ./frontend/package*.json ./
RUN npm install || npm install --no-package-lock

# Copy source
COPY ./frontend ./

EXPOSE 8080

ENV HOST=0.0.0.0
CMD ["npm", "run", "serve"]
