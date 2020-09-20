'use strict';

const ethereumjs = require('ethereumjs-abi');

/**
 * Read the documentation (https://strapi.io/documentation/v3.x/concepts/controllers.html#core-controllers)
 * to customize this controller
 */

module.exports = {
    async createVoucher(ctx) {
        try {
            const { amount, nonce, privateKey } = ctx.query

            if (!amount) throw { message: 'Amount is missing'}
            if (!nonce) throw { message: 'Nonce is missing'}
            if (!privateKey) throw { message: 'Private key is missing'}

            const hash = '0x' + ethereumjs.soliditySHA3(
                ['uint256', 'uint256'],
                [amount, nonce]
            ).toString('hex')

            const web3 = strapi.hook.web3Controller.getWeb3()
            const sig = web3.eth.accounts.sign(hash, privateKey)

            // Send 200 `ok`
            ctx.send({ signature: sig.signature })
        } catch (error) {
            return ctx.badRequest(null, [{ message: error.message }])
        }
    },

    async redeemVoucher(ctx) {
        try {
            const { amount, nonce, voucherId, privateKey } = ctx.query

            if (!amount) throw { message: 'Amount is missing'}
            if (!nonce) throw { message: 'Nonce is missing'}
            if (!voucherId) throw { message: 'Voucher Id is missing'}
            if (!privateKey) throw { message: 'Private key is missing'}

            const hash = '0x' + ethereumjs.soliditySHA3(
                ['uint256', 'uint256', 'uint256'],
                [amount, voucherId, nonce]
            ).toString('hex')

            const web3 = strapi.hook.web3Controller.getWeb3()
            const sig = web3.eth.accounts.sign(hash, privateKey)

            // Send 200 `ok`
            ctx.send({ signature: sig.signature })
        } catch (error) {
            return ctx.badRequest(null, [{ message: error.message }])
        }
    },

    async togglePartner(ctx) {
        try {
            const { partners, nonce, privateKey } = ctx.query

            if (!partners) throw { message: 'Partners are missing'}
            if (!nonce) throw { message: 'Nonce is missing'}
            if (!privateKey) throw { message: 'Private key is missing'}

            const hash = '0x' + ethereumjs.soliditySHA3(
                ['address[]', 'uint256'],
                [partners, nonce]
            ).toString('hex')

            const web3 = strapi.hook.web3Controller.getWeb3()
            const sig = web3.eth.accounts.sign(hash, privateKey)
            // Send 200 `ok`
            ctx.send({ signature: sig.signature })
        } catch (error) {
            return ctx.badRequest(null, [{ message: error.message }])
        }
    }
}