pragma solidity ^0.5.0;

import "./SafeMath.sol";
import "./ECDSA.sol";
import "./SignerRole.sol";

contract SmartVoucher is SignerRole {
    using ECDSA for bytes32;
    using SafeMath for uint256;

    uint256 private _lastId;

    struct Voucher {
        uint256 id;
        address webshop;
        uint256 amount;
        uint256 initialAmount;
        uint256 createdAt;
        uint256 nonce;
    }

    struct Webshop {
        uint256 nonce;
        uint256 lastActivity;
        mapping(uint256 => uint256) vouchersById;
    }

    mapping(uint256 => Voucher) private _vouchers;
    mapping(address => Webshop) private _webshops;

    event VoucherCreated(address indexed webshop, uint256 indexed amount, uint256 indexed id);
    event VoucherRedeemed(address indexed webshop, uint256 indexed amount, uint256 indexed id);

    //-------------
    // FALLBACK
    //-------------

    function () external payable {
        // Do nothing
    }

    //-------------
    // SETTERS
    //-------------

    function create(
        address webshop,
        uint256 amount,
        uint256 nonce,
        bytes calldata signature
    ) external onlySigner {
        require(webshop != address(0), "create: invalid webshop address");
        require(amount > 0, "create: amount should be bigger then 0");

        address signer = getSignerAddress(amount, nonce, signature);
        require(signer == webshop, "create: signed data is not correct");
        require(_webshops[webshop].nonce == nonce, "create: nonce is not correct");

        _vouchers[_lastId] = Voucher(_lastId, webshop, amount, amount, block.timestamp, 0);
        _webshops[webshop].vouchersById[_webshops[webshop].nonce] = _lastId;
        _webshops[webshop].lastActivity = block.timestamp;

        _lastId++;
        _webshops[webshop].nonce++;

        emit VoucherCreated(webshop, amount, _vouchers[_lastId].id);
    }

    function redeem(
        address webshop,
        uint256 amount,
        uint256 voucherId,
        uint256 nonce,
        bytes calldata signature
    ) external onlySigner {
        require(webshop != address(0), "redeem: invalid webshop address");
        require(amount > 0, "redeem: amount should be bigger then 0");
        require(voucherId >= 0 && voucherId < _lastId, "redeem: invalid voucherId address");
        require(_vouchers[voucherId].webshop == webshop, "redeem: the webshop doesn't own this voucher");
        require(_vouchers[voucherId].amount >= amount, "redeem: voucher amount is not enough");
        require(_vouchers[voucherId].nonce == nonce, "redeem: nonce is not correct");

        address signer = getSignerAddress(amount, voucherId, nonce, signature);
        require(signer == webshop, "create: signed data is not correct");

        _vouchers[voucherId].nonce++;
        _vouchers[voucherId].amount = _vouchers[voucherId].amount.sub(amount);
        _webshops[webshop].lastActivity = block.timestamp;

        emit VoucherRedeemed(webshop, amount, _vouchers[voucherId].id);
    }

    // -----------------------------------------
    // ECDSA GETTERS
    // -----------------------------------------

    function toEthSignedMessageHash(bytes32 hash) public pure returns (bytes32) {
        return hash.toEthSignedMessageHash();
    }

    function getSignerAddress(
        uint256 amount,
        uint256 nonce,
        bytes memory signature
    ) public pure returns (address) {
        bytes32 dataHash = keccak256(
            abi.encodePacked(
                amount,
                nonce
            )
        );

        bytes32 message = ECDSA.toEthSignedMessageHash(dataHash);
        return ECDSA.recover(message, signature);
    }

    function getSignerAddress(
        uint256 amount,
        uint256 voucherId,
        uint256 nonce,
        bytes memory signature
    ) public pure returns (address) {
        bytes32 dataHash = keccak256(
            abi.encodePacked(
                amount,
                voucherId,
                nonce
            )
        );

        bytes32 message = ECDSA.toEthSignedMessageHash(dataHash);
        return ECDSA.recover(message, signature);
    }

    //-------------
    // GETTERS
    //-------------

    function getLastId() external view returns (uint256) {
        return _lastId;
    }

    function getVoucherData(uint256 voucherId) public view returns (
        uint256 id,
        address webshop,
        uint256 amount,
        uint256 initialAmount,
        uint256 createdAt,
        uint256 nonce
    ) {
        Voucher memory voucher = _vouchers[voucherId];
        return (
            voucher.id,
            voucher.webshop,
            voucher.amount,
            voucher.initialAmount,
            voucher.createdAt,
            voucher.nonce
        );
    }

    function getWebshopData(address webshopAddr) external view returns (
        uint256 nonce,
        uint256 lastActivity
    ) {
        Webshop memory webshop = _webshops[webshopAddr];
        return (
            webshop.nonce,
            webshop.lastActivity
        );
    }

    function getVoucherByWebshop(address webshopAddr, uint256 order) external view returns (
        uint256 id,
        address webshop,
        uint256 amount,
        uint256 initialAmount,
        uint256 createdAt,
        uint256 nonce
    ) {
        uint256 voucherId = _webshops[webshopAddr].vouchersById[order];
        return getVoucherData(voucherId);
    }

    function isWebshopExist(address webshopAddr) external view returns (bool) {
        return _webshops[webshopAddr].lastActivity > 0;
    }

    function isVoucherOwnedByWebshop(address webshopAddr, uint256 voucherId) external view returns (bool) {
        return _vouchers[voucherId].webshop == webshopAddr;
    }
}