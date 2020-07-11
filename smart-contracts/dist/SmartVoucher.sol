
// File: contracts/SafeMath.sol

pragma solidity ^0.5.0;

/**
 * @dev Wrappers over Solidity's arithmetic operations with added overflow
 * checks.
 *
 * Arithmetic operations in Solidity wrap on overflow. This can easily result
 * in bugs, because programmers usually assume that an overflow raises an
 * error, which is the standard behavior in high level programming languages.
 * `SafeMath` restores this intuition by reverting the transaction when an
 * operation overflows.
 *
 * Using this library instead of the unchecked operations eliminates an entire
 * class of bugs, so it's recommended to use it always.
 */
library SafeMath {
    /**
     * @dev Returns the addition of two unsigned integers, reverting on
     * overflow.
     *
     * Counterpart to Solidity's `+` operator.
     *
     * Requirements:
     * - Addition cannot overflow.
     */
    function add(uint256 a, uint256 b) internal pure returns (uint256) {
        uint256 c = a + b;
        require(c >= a, "SafeMath: addition overflow");

        return c;
    }

    /**
     * @dev Returns the subtraction of two unsigned integers, reverting on
     * overflow (when the result is negative).
     *
     * Counterpart to Solidity's `-` operator.
     *
     * Requirements:
     * - Subtraction cannot overflow.
     */
    function sub(uint256 a, uint256 b) internal pure returns (uint256) {
        return sub(a, b, "SafeMath: subtraction overflow");
    }

    /**
     * @dev Returns the subtraction of two unsigned integers, reverting with custom message on
     * overflow (when the result is negative).
     *
     * Counterpart to Solidity's `-` operator.
     *
     * Requirements:
     * - Subtraction cannot overflow.
     *
     * _Available since v2.4.0._
     */
    function sub(uint256 a, uint256 b, string memory errorMessage) internal pure returns (uint256) {
        require(b <= a, errorMessage);
        uint256 c = a - b;

        return c;
    }

    /**
     * @dev Returns the multiplication of two unsigned integers, reverting on
     * overflow.
     *
     * Counterpart to Solidity's `*` operator.
     *
     * Requirements:
     * - Multiplication cannot overflow.
     */
    function mul(uint256 a, uint256 b) internal pure returns (uint256) {
        // Gas optimization: this is cheaper than requiring 'a' not being zero, but the
        // benefit is lost if 'b' is also tested.
        // See: https://github.com/OpenZeppelin/openzeppelin-contracts/pull/522
        if (a == 0) {
            return 0;
        }

        uint256 c = a * b;
        require(c / a == b, "SafeMath: multiplication overflow");

        return c;
    }

    /**
     * @dev Returns the integer division of two unsigned integers. Reverts on
     * division by zero. The result is rounded towards zero.
     *
     * Counterpart to Solidity's `/` operator. Note: this function uses a
     * `revert` opcode (which leaves remaining gas untouched) while Solidity
     * uses an invalid opcode to revert (consuming all remaining gas).
     *
     * Requirements:
     * - The divisor cannot be zero.
     */
    function div(uint256 a, uint256 b) internal pure returns (uint256) {
        return div(a, b, "SafeMath: division by zero");
    }

    /**
     * @dev Returns the integer division of two unsigned integers. Reverts with custom message on
     * division by zero. The result is rounded towards zero.
     *
     * Counterpart to Solidity's `/` operator. Note: this function uses a
     * `revert` opcode (which leaves remaining gas untouched) while Solidity
     * uses an invalid opcode to revert (consuming all remaining gas).
     *
     * Requirements:
     * - The divisor cannot be zero.
     *
     * _Available since v2.4.0._
     */
    function div(uint256 a, uint256 b, string memory errorMessage) internal pure returns (uint256) {
        // Solidity only automatically asserts when dividing by 0
        require(b > 0, errorMessage);
        uint256 c = a / b;
        // assert(a == b * c + a % b); // There is no case in which this doesn't hold

        return c;
    }

    /**
     * @dev Returns the remainder of dividing two unsigned integers. (unsigned integer modulo),
     * Reverts when dividing by zero.
     *
     * Counterpart to Solidity's `%` operator. This function uses a `revert`
     * opcode (which leaves remaining gas untouched) while Solidity uses an
     * invalid opcode to revert (consuming all remaining gas).
     *
     * Requirements:
     * - The divisor cannot be zero.
     */
    function mod(uint256 a, uint256 b) internal pure returns (uint256) {
        return mod(a, b, "SafeMath: modulo by zero");
    }

    /**
     * @dev Returns the remainder of dividing two unsigned integers. (unsigned integer modulo),
     * Reverts with custom message when dividing by zero.
     *
     * Counterpart to Solidity's `%` operator. This function uses a `revert`
     * opcode (which leaves remaining gas untouched) while Solidity uses an
     * invalid opcode to revert (consuming all remaining gas).
     *
     * Requirements:
     * - The divisor cannot be zero.
     *
     * _Available since v2.4.0._
     */
    function mod(uint256 a, uint256 b, string memory errorMessage) internal pure returns (uint256) {
        require(b != 0, errorMessage);
        return a % b;
    }
}

// File: contracts/ECDSA.sol

pragma solidity ^0.5.0;

/**
 * @dev Elliptic Curve Digital Signature Algorithm (ECDSA) operations.
 *
 * These functions can be used to verify that a message was signed by the holder
 * of the private keys of a given address.
 */
library ECDSA {
    /**
     * @dev Returns the address that signed a hashed message (`hash`) with
     * `signature`. This address can then be used for verification purposes.
     *
     * The `ecrecover` EVM opcode allows for malleable (non-unique) signatures:
     * this function rejects them by requiring the `s` value to be in the lower
     * half order, and the `v` value to be either 27 or 28.
     *
     * IMPORTANT: `hash` _must_ be the result of a hash operation for the
     * verification to be secure: it is possible to craft signatures that
     * recover to arbitrary addresses for non-hashed data. A safe way to ensure
     * this is by receiving a hash of the original message (which may otherwise
     * be too long), and then calling {toEthSignedMessageHash} on it.
     */
    function recover(bytes32 hash, bytes memory signature) internal pure returns (address) {
        // Check the signature length
        if (signature.length != 65) {
            revert("ECDSA: invalid signature length");
        }

        // Divide the signature in r, s and v variables
        bytes32 r;
        bytes32 s;
        uint8 v;

        // ecrecover takes the signature parameters, and the only way to get them
        // currently is to use assembly.
        // solhint-disable-next-line no-inline-assembly
        assembly {
            r := mload(add(signature, 0x20))
            s := mload(add(signature, 0x40))
            v := byte(0, mload(add(signature, 0x60)))
        }

        // EIP-2 still allows signature malleability for ecrecover(). Remove this possibility and make the signature
        // unique. Appendix F in the Ethereum Yellow paper (https://ethereum.github.io/yellowpaper/paper.pdf), defines
        // the valid range for s in (281): 0 < s < secp256k1n ÷ 2 + 1, and for v in (282): v ∈ {27, 28}. Most
        // signatures from current libraries generate a unique signature with an s-value in the lower half order.
        //
        // If your library generates malleable signatures, such as s-values in the upper range, calculate a new s-value
        // with 0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141 - s1 and flip v from 27 to 28 or
        // vice versa. If your library also generates signatures with 0/1 for v instead 27/28, add 27 to v to accept
        // these malleable signatures as well.
        if (uint256(s) > 0x7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF5D576E7357A4501DDFE92F46681B20A0) {
            revert("ECDSA: invalid signature 's' value");
        }

        if (v != 27 && v != 28) {
            revert("ECDSA: invalid signature 'v' value");
        }

        // If the signature is valid (and not malleable), return the signer address
        address signer = ecrecover(hash, v, r, s);
        require(signer != address(0), "ECDSA: invalid signature");

        return signer;
    }

    /**
     * @dev Returns an Ethereum Signed Message, created from a `hash`. This
     * replicates the behavior of the
     * https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_sign[`eth_sign`]
     * JSON-RPC method.
     *
     * See {recover}.
     */
    function toEthSignedMessageHash(bytes32 hash) internal pure returns (bytes32) {
        // 32 is the length in bytes of hash,
        // enforced by the type signature above
        return keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", hash));
    }
}

// File: contracts/Context.sol

pragma solidity ^0.5.0;

/*
 * @dev Provides information about the current execution context, including the
 * sender of the transaction and its data. While these are generally available
 * via msg.sender and msg.data, they should not be accessed in such a direct
 * manner, since when dealing with GSN meta-transactions the account sending and
 * paying for execution may not be the actual sender (as far as an application
 * is concerned).
 *
 * This contract is only required for intermediate, library-like contracts.
 */
contract Context {
    // Empty internal constructor, to prevent people from mistakenly deploying
    // an instance of this contract, which should be used via inheritance.
    constructor () internal { }
    // solhint-disable-previous-line no-empty-blocks

    function _msgSender() internal view returns (address payable) {
        return msg.sender;
    }

    function _msgData() internal view returns (bytes memory) {
        this; // silence state mutability warning without generating bytecode - see https://github.com/ethereum/solidity/issues/2691
        return msg.data;
    }
}

// File: contracts/Ownable.sol

pragma solidity ^0.5.0;


/**
 * @dev Contract module which provides a basic access control mechanism, where
 * there is an account (an owner) that can be granted exclusive access to
 * specific functions.
 *
 * This module is used through inheritance. It will make available the modifier
 * `onlyOwner`, which can be applied to your functions to restrict their use to
 * the owner.
 */
contract Ownable is Context {
    address private _owner;

    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);

    /**
     * @dev Initializes the contract setting the deployer as the initial owner.
     */
    constructor () internal {
        address msgSender = _msgSender();
        _owner = msgSender;
        emit OwnershipTransferred(address(0), msgSender);
    }

    /**
     * @dev Returns the address of the current owner.
     */
    function owner() public view returns (address) {
        return _owner;
    }

    /**
     * @dev Throws if called by any account other than the owner.
     */
    modifier onlyOwner() {
        require(isOwner(_msgSender()), "Ownable: caller is not the owner");
        _;
    }

    /**
     * @dev Returns true if the caller is the current owner.
     */
    function isOwner(address account) public view returns (bool) {
        return account == _owner;
    }

    /**
     * @dev Leaves the contract without owner. It will not be possible to call
     * `onlyOwner` functions anymore. Can only be called by the current owner.
     *
     * NOTE: Renouncing ownership will leave the contract without an owner,
     * thereby removing any functionality that is only available to the owner.
     */
    function renounceOwnership() public onlyOwner {
        emit OwnershipTransferred(_owner, address(0));
        _owner = address(0);
    }

    /**
     * @dev Transfers ownership of the contract to a new account (`newOwner`).
     * Can only be called by the current owner.
     */
    function transferOwnership(address newOwner) public onlyOwner {
        _transferOwnership(newOwner);
    }

    /**
     * @dev Transfers ownership of the contract to a new account (`newOwner`).
     */
    function _transferOwnership(address newOwner) internal {
        require(newOwner != address(0), "Ownable: new owner is the zero address");
        emit OwnershipTransferred(_owner, newOwner);
        _owner = newOwner;
    }
}

// File: contracts/Roles.sol

pragma solidity ^0.5.0;

/**
 * @title Roles
 * @dev Library for managing addresses assigned to a Role.
 */
library Roles {
    struct Role {
        mapping (address => bool) bearer;
    }

    /**
     * @dev Give an account access to this role.
     */
    function add(Role storage role, address account) internal {
        require(!has(role, account), "Roles: account already has role");
        role.bearer[account] = true;
    }

    /**
     * @dev Remove an account's access to this role.
     */
    function remove(Role storage role, address account) internal {
        require(has(role, account), "Roles: account does not have role");
        role.bearer[account] = false;
    }

    /**
     * @dev Check if an account has this role.
     * @return bool
     */
    function has(Role storage role, address account) internal view returns (bool) {
        require(account != address(0), "Roles: account is the zero address");
        return role.bearer[account];
    }
}

// File: contracts/SignerRole.sol

pragma solidity ^0.5.0;



contract SignerRole is Ownable {
    using Roles for Roles.Role;

    event SignerAdded(address indexed account);
    event SignerRemoved(address indexed account);

    Roles.Role private _signers;

    modifier onlySigner() {
        require(isSigner(_msgSender()), "SignerRole: caller does not have the Signer role");
        _;
    }

    function isSigner(address account) public view returns (bool) {
        return _signers.has(account);
    }

    function addSigner(address account) public onlyOwner {
        _addSigner(account);
    }

    function renounceSigner(address account) public onlyOwner {
        _removeSigner(account);
    }

    function _addSigner(address account) internal {
        _signers.add(account);
        emit SignerAdded(account);
    }

    function _removeSigner(address account) internal {
        _signers.remove(account);
        emit SignerRemoved(account);
    }
}

// File: contracts/SmartVoucher.sol

pragma solidity ^0.5.0;




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
    event VoucherRedeemed(address indexed webshop, uint256 indexed amount, uint256 indexed id);
    event PartnerAdded(address indexed webshop, address partner);
    event PartnerRemoved(address indexed webshop,address partner);

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

        _webshops[webshop].nonce++;
        _vouchers[_lastId] = Voucher(_lastId, webshop, amount, amount, block.timestamp, address(0));

        _webshops[webshop].vouchersById[_webshops[webshop].vouchersCount] = _lastId;
        _webshops[webshop].vouchersCount++;
        _webshops[webshop].lastActivity = block.timestamp;

        _lastId++;

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
        require(_vouchers[voucherId].webshop == webshop || _webshops[_vouchers[voucherId].webshop].isPartner[webshop], "redeem: not allowed webshop");
        require(_vouchers[voucherId].amount >= amount, "redeem: voucher amount is not enough");
        require(_webshops[webshop].nonce == nonce, "redeem: nonce is not correct");

        address signer = getSignerAddress(amount, voucherId, nonce, signature);
        require(signer == webshop, "redeem: signed data is not correct");

        _webshops[webshop].nonce++;
        _vouchers[voucherId].amount = _vouchers[voucherId].amount.sub(amount);
        _vouchers[voucherId].lastRedeemedWebshop = webshop;
        _webshops[webshop].lastActivity = block.timestamp;

        emit VoucherRedeemed(webshop, amount, _vouchers[voucherId].id);
    }

    function togglePartner(
        address webshop,
        address partner,
        uint256 nonce,
        bytes calldata signature
    ) external onlySigner {
        require(webshop != address(0), "togglePartner: invalid webshop address");
        require(partner != address(0), "togglePartner: invalid partner address");
        require(_webshops[webshop].nonce == nonce, "redeem: nonce is not correct");

        address signer = getSignerAddress(partner, nonce, signature);
        require(signer == webshop, "redeem: signed data is not correct");

        _webshops[webshop].nonce++;
        Webshop storage ws = _webshops[webshop];

        bool isPartner = ws.isPartner[partner];
        ws.lastActivity = block.timestamp;

        if (isPartner) {
            // Remove partner from partners list
            ws.isPartner[partner] = false;
            for (uint256 index = 0; index < ws.partners.length; index++) {
                if (ws.partners[index] == partner) {
                    ws.partners[index] = ws.partners[ws.partners.length - 1];
                    delete ws.partners[ws.partners.length - 1];
                    ws.partners.length--;
                    break;
                }
            }

            emit PartnerRemoved(webshop, partner);
        } else {
            // Add partner to partners list
            ws.isPartner[partner] = true;
            ws.partners.push(partner);

            emit PartnerAdded(webshop, partner);
        }
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
        address partner,
        uint256 nonce,
        bytes memory signature
    ) public pure returns (address) {
        bytes32 dataHash = keccak256(
            abi.encodePacked(
                partner,
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

    function isWebshopPartner(address webshop, address partner) external view returns (bool isPartner) {
        isPartner = _webshops[webshop].isPartner[partner];
        return isPartner;
    }

    function isVoucherOwnedByWebshop(address webshopAddr, uint256 voucherId) external view returns (bool isOwned) {
        isOwned = _vouchers[voucherId].webshop == webshopAddr;
        return isOwned;
    }
}
