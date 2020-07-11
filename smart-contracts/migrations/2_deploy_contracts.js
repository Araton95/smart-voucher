const SmartVoucher = artifacts.require('SmartVoucher.sol')

module.exports = async (deployer, network, accounts) => {
    return deployer.deploy(SmartVoucher, accounts[0]).then(contractInstance => {
        contractInstance.addSigner('0x9C16c04bDA8904EC30a0D7045b276a3c66012Fd3', { from: accounts[0] })
    })
}