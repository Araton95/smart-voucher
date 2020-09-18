pragma solidity ^0.5.0;

import "./SafeMath.sol";
import "./ECDSA.sol";
import "./SignerRole.sol";

contract SmartVoucher is SignerRole {
    using ECDSA for bytes32;
    using SafeMath for uint256;

    uint256 private _lastId = 1;

    struct Voucher {
        uint256 id;
        address webshop;
        uint256 amount;
        uint256 initialAmount;
        uint256 createdAt;
        address lastRedeemedWebshop;
    }

    struct Webshop {
        uint256 nonce;
        uint256 vouchersCount;
        uint256 lastActivity;
        address[] partners;

        mapping(address => bool) isPartner;
        mapping(uint256 => uint256) vouchersById;
    }

    mapping(uint256 => Voucher) private _vouchers;
    mapping(address => Webshop) private _webshops;

    event VoucherCreated(address indexed webshop, uint256 indexed amount, uint256 indexed id);
    event VoucherRedeemed(address indexed webshop, uint256 indexed amount, uint256 updatedAmount, uint256 indexed id);
    event PartnersAdded(address indexed webshop, address[] partner);
    event PartnersRemoved(address indexed webshop,address[] partner);

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
        require(webshop != address(0), "create: Invalid webshop address.");
        require(amount > 0, "create: Amount should be bigger then 0.");

        address signer = getSignerAddress(amount, nonce, signature);
        require(signer == webshop, "create: Signed data is not correct.");
        require(_webshops[webshop].nonce == nonce, "create: Nonce is not correct.");

        uint256 voucherId = _lastId;

        _webshops[webshop].nonce++;
        _vouchers[voucherId] = Voucher(voucherId, webshop, amount, amount, block.timestamp, address(0));

        // Started from 1st element, not 0
        _webshops[webshop].vouchersById[_webshops[webshop].vouchersCount + 1] = voucherId;
        _webshops[webshop].vouchersCount++;
        _webshops[webshop].lastActivity = block.timestamp;

        _lastId++;

        emit VoucherCreated(webshop, amount, voucherId);
    }

    function redeem(
        address webshop,
        uint256 amount,
        uint256 voucherId,
        uint256 nonce,
        bytes calldata signature
    ) external onlySigner {
        require(webshop != address(0), "redeem: Invalid webshop address.");
        require(amount > 0, "redeem: Amount should be bigger then 0.");
        require(voucherId >= 0 && voucherId < _lastId, "redeem: Invalid voucherId address.");
        require(webshopAllowedRedeem(webshop, voucherId), "redeem: Not allowed webshop.");
        require(_vouchers[voucherId].amount >= amount, "redeem: Voucher amount is not enough.");
        require(_webshops[webshop].nonce == nonce, "redeem: Nonce is not correct.");

        address signer = getSignerAddress(amount, voucherId, nonce, signature);
        require(signer == webshop, "redeem: Signed data is not correct.");

        _webshops[webshop].nonce++;
        _vouchers[voucherId].amount = _vouchers[voucherId].amount.sub(amount);
        _vouchers[voucherId].lastRedeemedWebshop = webshop;
        _webshops[webshop].lastActivity = block.timestamp;

        emit VoucherRedeemed(webshop, amount, _vouchers[voucherId].amount, _vouchers[voucherId].id);
    }

    function addPartners(
        address webshop,
        address[] calldata partners,
        uint256 nonce,
        bytes calldata signature
    ) external onlySigner {
        require(webshop != address(0), "addPartners: Invalid webshop address.");
        require(partners.length != 0, "addPartners: Partners not found address.");
        require(_webshops[webshop].nonce == nonce, "addPartners: Nonce is not correct.");

        address signer = getSignerAddress(partners, nonce, signature);
        require(signer == webshop, "addPartners: Signed data is not correct.");

        Webshop storage ws = _webshops[webshop];

        for (uint256 index = 0; index < partners.length; index++) {
            // Add partner to partners list
            ws.isPartner[partners[index]] = true;
            ws.partners.push(partners[index]);
        }

        ws.lastActivity = block.timestamp;

        emit PartnersAdded(webshop, partners);
    }

    function removePartners(
        address webshop,
        address[] calldata partners,
        uint256 nonce,
        bytes calldata signature
    ) external onlySigner {
        require(webshop != address(0), "removePartners: Invalid webshop address.");
        require(partners.length != 0, "removePartners: Partners not found.");
        require(_webshops[webshop].nonce == nonce, "removePartners: nonce is not correct.");

        address signer = getSignerAddress(partners, nonce, signature);
        require(signer == webshop, "removePartners: Signed data is not correct.");

        Webshop storage ws = _webshops[webshop];

        for (uint256 index = 0; index < partners.length; index++) {
            // Remove partner from partners list
            ws.isPartner[partners[index]] = false;

            for (uint256 j = 0; j < ws.partners.length; j++) {
                if (ws.partners[j] == partners[index]) {
                    ws.partners[j] = ws.partners[ws.partners.length - 1];
                    delete ws.partners[ws.partners.length - 1];
                    ws.partners.length--;
                    break;
                }
            }
        }

        ws.nonce++;
        ws.lastActivity = block.timestamp;

        emit PartnersRemoved(webshop, partners);
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

    function getSignerAddress(
        address[] memory partners,
        uint256 nonce,
        bytes memory signature
    ) public pure returns (address) {
        bytes32 dataHash = keccak256(
            abi.encodePacked(
                partners,
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
        address lastRedeemedWebshop
    ) {
        Voucher memory voucher = _vouchers[voucherId];
        return (
            voucher.id,
            voucher.webshop,
            voucher.amount,
            voucher.initialAmount,
            voucher.createdAt,
            voucher.lastRedeemedWebshop
        );
    }

    function getWebshopData(address webshopAddr) external view returns (
        uint256 nonce,
        uint256 lastActivity,
        address[] memory partners,
        uint256 vouchersCount
    ) {
        Webshop memory webshop = _webshops[webshopAddr];
        return (
            webshop.nonce,
            webshop.lastActivity,
            webshop.partners,
            webshop.vouchersCount
        );
    }

    function getVoucherByWebshop(address webshopAddr, uint256 order) external view returns (
        uint256 id,
        address webshop,
        uint256 amount,
        uint256 initialAmount,
        uint256 createdAt,
        address lastRedeemedWebshop
    ) {
        uint256 voucherId = _webshops[webshopAddr].vouchersById[order];
        return getVoucherData(voucherId);
    }

    function isWebshopExist(address webshopAddr) external view returns (bool isExist) {
        isExist = _webshops[webshopAddr].lastActivity > 0;
        return isExist;
    }

    function webshopAllowedRedeem(address webshop, uint256 voucherId) public view returns (bool) {
        bool isOwnerPartner = isWebshopPartner(_vouchers[voucherId].webshop, webshop);
        bool voucherOwner = isVoucherOwnedByWebshop(webshop, voucherId);
        return voucherOwner || isOwnerPartner;
    }

    function isWebshopPartner(address webshop, address partner) public view returns (bool isPartner) {
        isPartner = _webshops[webshop].isPartner[partner];
        return isPartner;
    }

    function isVoucherOwnedByWebshop(address webshopAddr, uint256 voucherId) public view returns (bool isOwned) {
        isOwned = _vouchers[voucherId].webshop == webshopAddr;
        return isOwned;
    }
}