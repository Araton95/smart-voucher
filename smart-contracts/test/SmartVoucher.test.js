const assert = require('assert')
const { ether, constants, expectRevert } = require('openzeppelin-test-helpers')

const SmartVoucher = artifacts.require('./SmartVoucher.sol')

contract('Smart voucher contract tests', (accounts) => {
    const owner = accounts[0]
    const signer = accounts[1]

    before(async () => {
        // 1. Deploy voucher smart contract
        this.contractInstance = await SmartVoucher.new({ from: owner })

        // 2. Set signer address (it should be backend based address)
        await this.contractInstance.addSigner(signer, { from: owner })
    })


    describe('Initial data validation', async () => {
        it('Owner and signer roles validation', async () => {
            const isOwner = await this.contractInstance.isOwner(owner, { from: owner })
            const isSigner = await this.contractInstance.isSigner(signer, { from: owner })

            assert.deepEqual(isOwner, true)
            assert.deepEqual(isSigner, true)
        })
    })
})