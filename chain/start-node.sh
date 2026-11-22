#!/usr/bin/env bash
# Nyalakan node Hardhat, tunggu siap, deploy kontrak, lalu biarkan node jalan.
set -euo pipefail

PORT="${PORT:-8545}"
RPC="http://127.0.0.1:${PORT}"
export CHAIN_RPC_URL="$RPC"

npx hardhat node --hostname 0.0.0.0 --port "$PORT" &
NODE_PID=$!

echo "menunggu node di $RPC ..."
for _ in $(seq 1 60); do
    if node -e "fetch('$RPC',{method:'POST',headers:{'content-type':'application/json'},body:JSON.stringify({jsonrpc:'2.0',id:1,method:'eth_chainId',params:[]})}).then(r=>r.json()).then(()=>process.exit(0)).catch(()=>process.exit(1))"; then
        break
    fi
    sleep 1
done

npx hardhat run scripts/deploy.js --network localhost

echo "kontrak siap. alamat di atas harus sama dengan CHAIN_CONTRACT_ADDRESS di service web."
wait "$NODE_PID"
