const { ethers } = require("hardhat");

const pad = (s) => ethers.encodeBytes32String(s).toLowerCase();

async function main() {
    const [deployer] = await ethers.getSigners();
    const factory = await ethers.getContractFactory("GeoAnchor");
    const contract = await factory.deploy();

    await contract.waitForDeployment();

    const address = await contract.getAddress();

    console.log(JSON.stringify({
        address,
        deployer: deployer.address,
        chainId: (await ethers.provider.getNetwork()).chainId.toString(),
        rpc: process.env.CHAIN_RPC_URL || "http://127.0.0.1:8545",
        sample_actor: pad("User A"),
    }, null, 2));
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
