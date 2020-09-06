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

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: 'Invalid webshop address'}
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: 'Invalid signed data'}

            // Validate webshop address
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            if (!webshop) throw { message: 'Webshop not found'}

            // Send transaction to smart contract
            const voucherId = await strapi.hook.web3Controller.getVouchersCount()
            await strapi.hook.web3Controller.createVoucher({ webshopAddr, amount, nonce, signature })

            // Save voucher to DB
            await strapi.services.voucher.create({
                'voucherId': voucherId,
                'initialAmount': strapi.hook.helpers.centsToUsd(amount),
                'currentAmount': strapi.hook.helpers.centsToUsd(amount),
                'webshop': webshop['id']
            })

            // Encode voucher id to encrypted data
            const encodedCode = strapi.hook.encoder.encode(voucherId)

            // Send 200 `ok`
            ctx.send({  ok: true, voucherCode: encodedCode })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(":").length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async redeem(ctx) {
        try {
            const { webshopAddr, amount, voucherId, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: 'Webshop address is missing'}
            if (!amount) throw { message: 'Amount is missing'}
            if (!voucherId) throw { message: 'Voucher code is missing'}
            if (!nonce) throw { message: 'Nonce is missing'}
            if (!signature) throw { message: 'Signature is missing'}

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: 'Invalid webshop address'}
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: 'Invalid signed data'}

            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            if (!webshop) throw { message: 'Webshop not found'}

            // Validate voucher id
            const voucher = await strapi.services.voucher.findOne({ voucherId })
            if (!voucher) throw { message: 'Voucher not found'}

            // Send transaction to smart contract
            await strapi.hook.web3Controller.redeemVoucher({ webshopAddr, amount, voucherId, nonce, signature })
            const voucherDataContract = await strapi.hook.web3Controller.getVoucherData(voucherId)
            const voucherBalance = centsToUsd(voucherDataContract['amount'].toString())
            const updatedVoucher = JSON.stringify({ currentAmount: voucherBalance })

            // Update voucher data on DB
            await strapi.services.voucher.update({ id: voucher.id }, updatedVoucher)

            // Send 200 `ok`
            ctx.send({ ok: true, voucherBalance  })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(":").length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async validateCode(ctx) {
        try {
            const { voucherCode } = ctx.query
            if (!voucherCode) throw { message: 'Voucher code is missing'}

            // Decode encrypted voucher code to voucher Id
            const voucherId = strapi.hook.encoder.decode(voucherCode)

            const voucher = await strapi.services.voucher.findOne({ voucherId })
            if (!voucher) {
                throw { message: 'Voucher not found' }
            } else if (voucher.blocked) {
                throw { message: 'Voucher is blocked' }
            }

            // Send 200 `ok`
            ctx.send({ voucherId })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(":").length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    }
}
