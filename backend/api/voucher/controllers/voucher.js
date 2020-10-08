'use strict'

const { sanitizeEntity } = require('strapi-utils')
const { texts } = require('../../../config/texts')

/**
 * Read the documentation (https://strapi.io/documentation/v3.x/concepts/controllers.html#core-controllers)
 * to customize this controller
 */

module.exports = {
    async create(ctx) {
        try {

            // Encode voucher id to encrypted data
            const codd = strapi.hook.encoder.encode('5')

            // Send 200 `ok`
            ctx.send({  ok: true, voucherCode: codd })
            return

            const { webshopAddr, amount, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: texts.missing_webshop }
            if (!amount) throw { message: texts.missing_amount }
            if (!nonce) throw { message: texts.missing_nonce }
            if (!signature) throw { message: texts.missing_signature }

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: texts.invalid_webshop }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: texts.invalid_signature }

            // Validate webshop address
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })

            // Validate webshop exist
            if (!webshop) {
                throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
                throw { message: texts.blocked_webshop }
            }

            // Get the last voucher id from contract
            const voucherId = (await strapi.hook.web3Controller.getVouchersCount()).toString()

            // Send transaction to smart contract
            const tx = await strapi.hook.web3Controller.createVoucher({ webshopAddr, amount, nonce, signature })

            console.log('Create new voucher tx to smart contract:\n', tx.transactionHash)
            console.log('Tx mined!')

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

            if (!message) {
                message = "Something goes wrong. Please try again later"
            } else if (message.length && message.split(":").length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, message)
        }
    },

    async redeem(ctx) {
        try {
            const { webshopAddr, amount, voucherId, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: texts.missing_webshop }
            if (!amount) throw { message: texts.missing_amount }
            if (!voucherId) throw { message: texts.missing_voucher_id }
            if (!nonce) throw { message: texts.missing_nonce }
            if (!signature) throw { message: texts.missing_signature }

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: texts.invalid_webshop }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: texts.invalid_signature }

            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            // Validate webshop exist
            if (!webshop) {
              throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
              throw { message: texts.blocked_webshop }
            }

            // Validate voucher id
            const voucher = await strapi.services.voucher.findOne({ voucherId })
            if (!voucher) {
                throw { message: texts.voucher_not_exists }
            } else if (voucher.blocked) {
                throw { message: texts.blocked_voucher }
            }

            // Send transaction to smart contract
            const tx = await strapi.hook.web3Controller.redeemVoucher({ webshopAddr, amount, voucherId, nonce, signature })

            console.log('Redeem voucher tx:\n', tx.transactionHash)
            console.log('Tx mined!')

            // Fetch updated data from contract
            const voucherDataContract = await strapi.hook.web3Controller.getVoucherData(voucherId)

            // Update the Strapi DB balance
            const voucherBalance = strapi.hook.helpers.centsToUsd(voucherDataContract['amount'].toString())
            const updatedVoucher = JSON.stringify({ currentAmount: voucherBalance })

            // Update voucher data on DB
            await strapi.services.voucher.update({ id: voucher.id }, updatedVoucher)

            // Send 200 `ok`
            ctx.send({ ok: true, voucherBalance  })
        } catch (error) {
            let { message } = error

            if (!message) {
                message = "Something goes wrong. Please try again later"
            } else if (message.length && message.split(":").length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return  ctx.badRequest(null, message)
        }
    },

    async validateCode(ctx) {
        try {
            const { voucherCode, webshopAddr } = ctx.query
            if (!voucherCode) throw { message: texts.missing_voucher_code }
            if (!webshopAddr) throw { message: texts.missing_webshop }

            // Decode encrypted voucher code to voucher Id
            const voucherId = strapi.hook.encoder.decode(voucherCode)

            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })
            // Validate webshop exist
            if (!webshop) {
              throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
              throw { message: texts.blocked_webshop }
            }

            // Validate voucher id
            const voucher = await strapi.services.voucher.findOne({ voucherId })
            if (!voucher) {
                throw { message: texts.voucher_not_exists }
            } else if (voucher.blocked) {
                throw { message: texts.blocked_voucher }
            }

            const allowedRedeem = await strapi.hook.web3Controller.allowedToRedeem(webshopAddr, voucherId)
            if (!allowedRedeem) {
                throw { message: texts.not_allowed_redeem }
            }

            // Send 200 `ok`
            ctx.send({ voucherId })
        } catch (error) {
            let { message } = error

            if (!message) {
                message = "Something goes wrong. Please try again later"
            } else if (message.length && message.split(":").length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            console.log(message)
            return ctx.badRequest(null, message)
        }
    }
}
