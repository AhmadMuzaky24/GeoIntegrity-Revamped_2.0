// Writes one anchor to the contract and prints JSON for the PHP caller.
// usage: node scripts/anchor.js <locationId> <dataHashHex> <actorName>
const { ethers } = require("ethers");

async function main() {
    const [locationId, dataHash, actor] = process.argv.slice(2);

    if (!locationId || !dataHash || !actor) {
        throw new Error("usage: anchor.js <locationId> <dataHashHex> <actorName>");
    }

    const rpc = process.env.CHAIN_RPC_URL || "http://127.0.0.1:8545";
    const address = process.env.CHAIN_CONTRACT_ADDRESS;
    const key = process.env.CHAIN_DEPLOYER_KEY;

    if (!address || !key) {
        throw new Error("CHAIN_CONTRACT_ADDRESS and CHAIN_DEPLOYER_KEY must be set");
    }

    const provider = new ethers.JsonRpcProvider(rpc);
    const wallet = new ethers.Wallet(key, provider);
    const contract = new ethers.Contract(address, [
        "function anchor(uint256 locationId, bytes32 dataHash, bytes32 actor) returns (uint256)",
        "function countFor(uint256 locationId) view returns (uint256)",
    ], wallet);

    const hash = dataHash.startsWith("0x") ? dataHash : "0x" + dataHash;
    const actorBytes = ethers.encodeBytes32String(actor);

    const tx = await contract.anchor(BigInt(locationId), hash, actorBytes);
    const receipt = await tx.wait();

    const count = await contract.countFor(BigInt(locationId));

    console.log(JSON.stringify({
        tx: receipt.hash,
        block: receipt.blockNumber,
        location_id: Number(locationId),
        index: Number(count) - 1,
    }));
}

main().catch((error) => {
    console.error(JSON.stringify({ error: error.shortMessage || error.message }));
    process.exitCode = 1;
});
