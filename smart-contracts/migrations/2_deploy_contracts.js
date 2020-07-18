const SmartVoucher = artifacts.require('SmartVoucher.sol')

module.exports = async (deployer, network, accounts) => {
    return deployer.deploy(SmartVoucher, accounts[0]).then(contractInstance => {
        contractInstance.addSigner(process.env.SIGNER, { from: accounts[0] })
    })
}