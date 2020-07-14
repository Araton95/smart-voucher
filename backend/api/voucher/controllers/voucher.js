'use strict'

const { sanitizeEntity } = require('strapi-utils')

/**
 * Read the documentation (https://strapi.io/documentation/v3.x/concepts/controllers.html#core-controllers)
 * to customize this controller
 */

module.exports = {
    async create(ctx) {
        try {
            const { webshopAddr, amount, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: 'Webshop address is missing'}
            if (!amount) throw { message: 'Amount is missing'}
            if (!nonce) throw { message: 'Nonce is missing'}
            if (!signature) throw { message: 'Signature is missing'}

            // Validate webshop address
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            if (!webshop) throw { message: 'Webshop not found'}

            // Send transaction to smart contract
            const receipt = await strapi.hook.web3Controller.createVoucher({ webshopAddr, amount, nonce, signature })
            const voucherId = receipt.logs[0].args['id'].toString()

            // Save voucher to DB
            await strapi.services.voucher.create({
                'voucherId': voucherId,
                'initialAmount': amount,
                'currentAmount': amount,
                'webshop': webshop['id']
            })

            // Encode voucher id to encrypted data
            const encode = strapi.hook.encoder.encode(voucherId)

            // Send 200 `ok`
            ctx.send({ voucherCode: encode })
        } catch (error) {
            return ctx.badRequest(null, [{ message: error.message }])
        }
    },

    async redeem(ctx) {
        try {
            const { webshopAddr, amount, voucherCode, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: 'Webshop address is missing'}
            if (!amount) throw { message: 'Amount is missing'}
            if (!voucherCode) throw { message: 'Voucher code is missing'}
            if (!nonce) throw { message: 'Nonce is missing'}
            if (!signature) throw { message: 'Signature is missing'}

            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            if (!webshop) throw { message: 'Webshop not found'}

            // Decode encrypted voucher code to voucher Id
            const voucherId = strapi.hook.encoder.decode(voucherCode)

            // Validate voucher id
            const voucher = await strapi.services.voucher.findOne({ 'voucherId': voucherId })
            if (!voucher) throw { message: 'Voucher not found'}

            // Send transaction to smart contract
            const receipt = await strapi.hook.web3Controller.redeemVoucher({ webshopAddr, amount, voucherId, nonce, signature })
            const updatedVoucher = JSON.stringify({ currentAmount: receipt.logs[0].args['updatedAmount'].toString() })

            // Update voucher data on DB
            await strapi.services.voucher.update({ id: voucher.id }, updatedVoucher)

            // Send 200 `ok`
            ctx.send({ ok: true })
        } catch (error) {
            return ctx.badRequest(null, [{ message: error.message }])
        }
    }
}
