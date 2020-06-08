pragma solidity ^0.5.0;

import "./SafeMath.sol";
import "./SignerRole.sol";

contract SmartVoucher is SignerRole {
    using SafeMath for uint256;

    uint256 private _lastId;

    struct Voucher {
        uint256 id;
        address webshop;
        uint256 amount;
        uint256 initialAmount;
        uint256 createdAt;
    }

    struct Webshop {
        uint256 lastActivity;
        uint256 vouchersCount;
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

    function create(address webshop, uint256 amount) external onlySigner {
        require(webshop != address(0), "create: invalid webshop address");
        require(amount > 0, "create: amount should be bigger then 0");

        _vouchers[_lastId] = Voucher(_lastId, webshop, amount, amount, block.timestamp);
        _webshops[webshop].vouchersById[_webshops[webshop].vouchersCount] = _lastId;
        _webshops[webshop].lastActivity = block.timestamp;

        _lastId++;
        _webshops[webshop].vouchersCount++;

        emit VoucherCreated(webshop, amount, _vouchers[_lastId].id);
    }

    function redeem(address webshop, uint256 amount, uint256 voucherId) external onlySigner {
        require(webshop != address(0), "redeem: invalid webshop address");
        require(amount > 0, "redeem: amount should be bigger then 0");
        require(voucherId > 0 && voucherId < _lastId, "redeem: invalid voucherId address");
        require(_vouchers[voucherId].webshop == webshop, "redeem: the webshop doesn't own this voucher");
        require(_vouchers[voucherId].amount >= amount, "redeem: voucher amount is not enough");

        _vouchers[voucherId].amount = _vouchers[voucherId].amount.sub(amount);
        _webshops[webshop].lastActivity = block.timestamp;

        emit VoucherRedeemed(webshop, amount, _vouchers[voucherId].id);
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
        uint256 createdAt
    ) {
        Voucher memory voucher = _vouchers[voucherId];
        return (
            voucher.id,
            voucher.webshop,
            voucher.amount,
            voucher.initialAmount,
            voucher.createdAt
        );
    }

    function getWebshopData(address webshopAddr) external view returns (
        uint256 vouchersCount,
        uint256 lastActivity
    ) {
        Webshop memory webshop = _webshops[webshopAddr];
        return (
            webshop.vouchersCount,
            webshop.lastActivity
        );
    }

    function getVoucherByWebshop(address webshopAddr, uint256 order) external view returns (
        uint256 id,
        address webshop,
        uint256 amount,
        uint256 initialAmount,
        uint256 createdAt
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