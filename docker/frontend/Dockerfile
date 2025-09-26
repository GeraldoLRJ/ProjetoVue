# Vue 2
FROM node:18-alpine

WORKDIR /app

COPY ./frontend/package*.json ./
RUN npm install || npm install --no-package-lock

COPY ./frontend ./

EXPOSE 8080

ENV HOST=0.0.0.0
CMD ["npm", "run", "serve"]
