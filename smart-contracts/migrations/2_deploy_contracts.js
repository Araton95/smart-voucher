const SmartVoucher = artifacts.require('SmartVoucher.sol')

module.exports = async (deployer, network, accounts) => {
    return deployer.deploy(SmartVoucher, accounts[0])
}