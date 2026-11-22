# Image untuk service "chain" (opsi B: node Hardhat di Railway).
# Peringatan: state node disimpan di memori, jadi anchor hilang setiap restart.

FROM node:20-bookworm-slim

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

EXPOSE 8545

CMD ["bash", "start-node.sh"]
