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

            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            if (!webshop) throw { message: 'Webshop not found'}

            const receipt = await strapi.hook.web3Controller.createVoucher({ webshopAddr, amount, nonce, signature })

            const entity = await strapi.services.voucher.create({
                'voucherId': receipt.logs[0].args['id'].toString(),
                'initialAmount': amount,
                'currentAmount': amount,
                'webshop': webshop['id']
            })

            // Send 200 `ok`
            return sanitizeEntity(entity, { model: strapi.models.voucher })
        } catch (error) {
            return ctx.badRequest(null, [{ message: error.message }])
        }
    },

    async redeem(ctx) {
        try {
            const { webshopAddr, amount, voucherId, nonce, signature } = ctx.request.body
            if (!webshopAddr) throw { message: 'Webshop address is missing'}
            if (!amount) throw { message: 'Amount is missing'}
            if (!voucherId) throw { message: 'Voucher id is missing'}
            if (!nonce) throw { message: 'Nonce is missing'}
            if (!signature) throw { message: 'Signature is missing'}

            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            if (!webshop) throw { message: 'Webshop not found'}

            const voucher = await strapi.services.voucher.findOne({ 'voucherId': voucherId })
            if (!voucher) throw { message: 'Voucher not found'}

            const receipt = await strapi.hook.web3Controller.redeemVoucher({ webshopAddr, amount, voucherId, nonce, signature })
            const updatedVoucher = JSON.stringify({ currentAmount: receipt.logs[0].args['updatedAmount'].toString() })

            await strapi.services.voucher.update({ id: voucher.id }, updatedVoucher)

            // Send 200 `ok`
            ctx.send({ ok: true })
        } catch (error) {
            return ctx.badRequest(null, [{ message: error.message }])
        }
    }
}
