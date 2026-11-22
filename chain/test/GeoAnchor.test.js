const { expect } = require("chai");
const { ethers } = require("hardhat");

describe("GeoAnchor", () => {
    let contract;
    const hashA = ethers.keccak256(ethers.toUtf8Bytes("point-a-v1"));
    const hashB = ethers.keccak256(ethers.toUtf8Bytes("point-a-v2"));
    const actor = ethers.encodeBytes32String("User A");

    beforeEach(async () => {
        const factory = await ethers.getContractFactory("GeoAnchor");
        contract = await factory.deploy();
        await contract.waitForDeployment();
    });

    it("starts empty", async () => {
        expect(await contract.total()).to.equal(0n);
        expect(await contract.countFor(1n)).to.equal(0n);
    });

    it("stores an anchor and exposes it by index", async () => {
        await contract.anchor(1n, hashA, actor);

        expect(await contract.total()).to.equal(1n);
        expect(await contract.countFor(1n)).to.equal(1n);

        const [locationId, dataHash, storedActor] = await contract.anchorAt(0n);

        expect(locationId).to.equal(1n);
        expect(dataHash).to.equal(hashA);
        expect(storedActor).to.equal(actor);
    });

    it("latestFor returns the newest hash for a location", async () => {
        await contract.anchor(1n, hashA, actor);
        await contract.anchor(1n, hashB, actor);

        const [dataHash, , , index] = await contract.latestFor(1n);

        expect(dataHash).to.equal(hashB);
        expect(index).to.equal(1n);
    });

    it("keeps history: old hash is never replaced", async () => {
        await contract.anchor(1n, hashA, actor);
        await contract.anchor(1n, hashB, actor);

        const [, firstHash] = await contract.anchorAt(0n);

        expect(firstHash).to.equal(hashA);
        expect(await contract.countFor(1n)).to.equal(2n);
    });

    it("reverts latestFor for unknown location", async () => {
        let message = "";

        try {
            await contract.latestFor(99n);
        } catch (error) {
            message = error.message;
        }

        expect(message).to.include("no anchor for location");
    });

    it("emits Anchored", async () => {
        const receipt = await (await contract.anchor(7n, hashA, actor)).wait();

        const event = receipt.logs
            .map((log) => {
                try {
                    return contract.interface.parseLog(log);
                } catch {
                    return null;
                }
            })
            .find((parsed) => parsed && parsed.name === "Anchored");

        expect(event, "Anchored event missing").to.not.equal(undefined);
        expect(event.args.locationId).to.equal(7n);
        expect(event.args.dataHash).to.equal(hashA);
        expect(event.args.actor).to.equal(actor);
        expect(event.args.index).to.equal(0n);
    });
});
