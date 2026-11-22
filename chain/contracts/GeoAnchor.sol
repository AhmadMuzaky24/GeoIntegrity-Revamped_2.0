// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/// @title GeoAnchor
/// @notice Append-only registry of geo point credential hashes.
/// @dev Permissionless on purpose: the Laravel app decides who may write, the
///      contract only guarantees that a hash, once stored, can never be replaced.
///      Every value is fixed-size so callers can encode/decode without an ABI
///      library (the PHP side talks raw JSON-RPC).
contract GeoAnchor {
    struct Anchor {
        uint256 locationId;
        bytes32 dataHash;
        bytes32 actor;
        uint64 timestamp;
    }

    Anchor[] private anchors;
    mapping(uint256 => uint256[]) private byLocation;

    event Anchored(
        uint256 indexed locationId,
        bytes32 dataHash,
        bytes32 actor,
        uint256 index,
        uint64 timestamp
    );

    function anchor(uint256 locationId, bytes32 dataHash, bytes32 actor)
        external
        returns (uint256 index)
    {
        index = anchors.length;
        uint64 ts = uint64(block.timestamp);

        anchors.push(Anchor(locationId, dataHash, actor, ts));
        byLocation[locationId].push(index);

        emit Anchored(locationId, dataHash, actor, index, ts);
    }

    function total() external view returns (uint256) {
        return anchors.length;
    }

    function anchorAt(uint256 index)
        external
        view
        returns (uint256 locationId, bytes32 dataHash, bytes32 actor, uint64 timestamp)
    {
        Anchor storage a = anchors[index];

        return (a.locationId, a.dataHash, a.actor, a.timestamp);
    }

    function countFor(uint256 locationId) external view returns (uint256) {
        return byLocation[locationId].length;
    }

    function latestFor(uint256 locationId)
        external
        view
        returns (bytes32 dataHash, bytes32 actor, uint64 timestamp, uint256 index)
    {
        uint256[] storage indexes = byLocation[locationId];

        require(indexes.length > 0, "no anchor for location");

        index = indexes[indexes.length - 1];
        Anchor storage a = anchors[index];

        return (a.dataHash, a.actor, a.timestamp, index);
    }
}
