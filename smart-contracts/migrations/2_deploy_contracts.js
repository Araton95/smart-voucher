const SmartVoucher = artifacts.require('SmartVoucher.sol')

module.exports = async (deployer, network, accounts) => {
    return deployer.deploy(SmartVoucher, accounts[0]).then(contractInstance => {
        contractInstance.addSigner('0x378810509345707Ca128A31123f0e594fA9bf46c', { from: accounts[0] })
    })
}