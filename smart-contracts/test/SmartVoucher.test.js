const assert = require('assert')
const Web3 = require('web3')
const ethereumjs = require('ethereumjs-abi')
const { ether, constants, expectRevert } = require('openzeppelin-test-helpers')

const SmartVoucher = artifacts.require('./SmartVoucher.sol')
const web3 = new Web3('http://127.0.0.1:8545')

contract('Smart voucher contract tests', (accounts) => {
    const owner = accounts[0]
    const signer = accounts[1]

    const webshop1 = accounts[2]
    const webshop2 = accounts[3]

    const webshop1PK = process.env.WEBSHOP1PK
    const webshop2PK = process.env.WEBSHOP2PK

    const signData = async (amount, nonce, signerPk) => {
        const hash = '0x' + ethereumjs.soliditySHA3(
            ['uint256', 'uint256'],
            [amount, nonce]
        ).toString('hex')

        const sig = await web3.eth.accounts.sign(hash, signerPk)
        return sig.signature
    }

    const signRedeemData = async (amount, voucherId, nonce, signerPk) => {
        const hash = '0x' + ethereumjs.soliditySHA3(
            ['uint256', 'uint256', 'uint256'],
            [amount, voucherId, nonce]
        ).toString('hex')

        const sig = await web3.eth.accounts.sign(hash, signerPk)
        return sig.signature
    }

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
            const lastId = await this.contractInstance.getLastId({ from: owner })

            assert.deepEqual(isOwner, true)
            assert.deepEqual(isSigner, true)
            assert.deepEqual(lastId.toString(), '0')
        })
    })

    describe('Create voucher validations', async () => {
        it('Check the webshop state before voucher creation', async () => {
            const isWebshop1Exist = await this.contractInstance.isWebshopExist(webshop1, ({ from: owner }))
            const webshop1Data = await this.contractInstance.getWebshopData(webshop1, { from: owner })

            assert.deepEqual(isWebshop1Exist, false)
            assert.deepEqual(webshop1Data['nonce'].toString(), '0')
            assert.deepEqual(webshop1Data['lastActivity'].toString(), '0')
        })

        it('Webshop sign and create new voucher', async () => {
            const webshopData = await this.contractInstance.getWebshopData(webshop1, { from: webshop1 })
            const nonce = webshopData['nonce'].toString()
            const testAmount = ether('10').toString()

            const signature = await signData(testAmount, nonce, webshop1PK)
            const signerAddressByContract = await this.contractInstance.getSignerAddress(testAmount, nonce, signature)

            assert.deepEqual(signerAddressByContract, webshop1)

            await this.contractInstance.create(webshop1, testAmount, nonce, signature, { from: signer })
        })

        it('Check state after voucher creation', async () => {
            const isWebshop1Exist = await this.contractInstance.isWebshopExist(webshop1, ({ from: owner }))
            const webshop1Data = await this.contractInstance.getWebshopData(webshop1, { from: owner })

            assert.deepEqual(isWebshop1Exist, true)
            assert.deepEqual(webshop1Data['nonce'].toString(), '1')
            assert.notDeepEqual(webshop1Data['lastActivity'].toString(), '0')

            const voucherData = await this.contractInstance.getVoucherByWebshop(webshop1, '0', ({ from: owner}))
            assert.deepEqual(voucherData['id'].toString(), '0')
            assert.deepEqual(voucherData['webshop'], webshop1)
            assert.deepEqual(voucherData['amount'].toString(), ether('10').toString())
            assert.deepEqual(voucherData['initialAmount'].toString(), ether('10').toString())
            assert.notDeepEqual(voucherData['createdAt'].toString(), '0')
            assert.deepEqual(voucherData['nonce'].toString(), '0')
        })
    })

    describe('Redeem voucher validations', async () => {
        it('Check revert with other webshop signature', async () => {
            // Values from previous test
            const id = '0'
            const nonce = '0'
            const redeemAmount = ether('3').toString()
            const signature = await signRedeemData(redeemAmount, id, nonce, webshop2PK)

            await expectRevert(
                this.contractInstance.redeem(webshop1, redeemAmount, id, nonce, signature, { from: signer }),
                'redeem: signed data is not correct'
            )
        })

        it('Webshop sign and redeem voucher', async () => {
            const voucherData = await this.contractInstance.getVoucherByWebshop(webshop1, '0', { from: owner })
            const id = voucherData['id'].toString()
            const nonce = voucherData['nonce'].toString()
            const redeemAmount = ether('3').toString()

            const signature = await signRedeemData(redeemAmount, id, nonce, webshop1PK)
            const signerAddressByContract = await this.contractInstance.getSignerAddress(redeemAmount, id, nonce, signature)

            assert.deepEqual(signerAddressByContract, webshop1)

            await this.contractInstance.redeem(webshop1, redeemAmount, id, nonce, signature, { from: signer })
        })

        it('Check state after redeem action', async () => {
            const voucherData = await this.contractInstance.getVoucherByWebshop(webshop1, '0', { from: owner })
            assert.deepEqual(voucherData['id'].toString(), '0')
            assert.deepEqual(voucherData['amount'].toString(), ether('7').toString()) // => 10 - 3
            assert.deepEqual(voucherData['initialAmount'].toString(), ether('10').toString())
            assert.deepEqual(voucherData['nonce'].toString(), '1')
        })

        it('Check revert with same signature', async () => {
            // Values from previous test
            const id = '0'
            const nonce = '0'
            const redeemAmount = ether('3').toString()
            const signature = await signRedeemData(redeemAmount, id, nonce, webshop1PK)

            await expectRevert(
                this.contractInstance.redeem(webshop1, redeemAmount, id, nonce, signature, { from: signer }),
                'redeem: nonce is not correct'
            )
        })

        it('Check revert with bigger value', async () => {
            const voucherData = await this.contractInstance.getVoucherByWebshop(webshop1, '0', { from: owner })
            const id = voucherData['id'].toString()
            const nonce = voucherData['nonce'].toString()
            const wrongRedeemAmount = ether('30').toString()

            const signature = await signRedeemData(wrongRedeemAmount, id, nonce, webshop1PK)

            await expectRevert(
                this.contractInstance.redeem(webshop1, wrongRedeemAmount, id, nonce, signature, { from: signer }),
                'redeem: voucher amount is not enough'
            )
        })
    })

    describe('Create voucher with another webshop validations', async () => {
        it('Check the webshop state before voucher creation', async () => {
            const isWebshop2Exist = await this.contractInstance.isWebshopExist(webshop2, ({ from: owner }))
            const webshop2Data = await this.contractInstance.getWebshopData(webshop2, { from: owner })

            assert.deepEqual(isWebshop2Exist, false)
            assert.deepEqual(webshop2Data['nonce'].toString(), '0')
            assert.deepEqual(webshop2Data['lastActivity'].toString(), '0')
        })

        it('Webshop sign and create new voucher', async () => {
            const webshopData = await this.contractInstance.getWebshopData(webshop2, { from: webshop2 })
            const nonce = webshopData['nonce'].toString()
            const testAmount = ether('20').toString()

            const signature = await signData(testAmount, nonce, webshop2PK)
            const signerAddressByContract = await this.contractInstance.getSignerAddress(testAmount, nonce, signature)

            assert.deepEqual(signerAddressByContract, webshop2)

            await this.contractInstance.create(webshop2, testAmount, nonce, signature, { from: signer })
        })

        it('Check state after voucher creation', async () => {
            const isWebshop2Exist = await this.contractInstance.isWebshopExist(webshop2, ({ from: owner }))
            const webshop2Data = await this.contractInstance.getWebshopData(webshop2, { from: owner })

            assert.deepEqual(isWebshop2Exist, true)
            assert.deepEqual(webshop2Data['nonce'].toString(), '1')
            assert.notDeepEqual(webshop2Data['lastActivity'].toString(), '0')

            const voucherData = await this.contractInstance.getVoucherByWebshop(webshop2, '0', ({ from: owner}))
            assert.deepEqual(voucherData['id'].toString(), '1')
            assert.deepEqual(voucherData['webshop'], webshop2)
            assert.deepEqual(voucherData['amount'].toString(), ether('20').toString())
            assert.deepEqual(voucherData['initialAmount'].toString(), ether('20').toString())
            assert.notDeepEqual(voucherData['createdAt'].toString(), '0')
            assert.deepEqual(voucherData['nonce'].toString(), '0')
        })
    })
})