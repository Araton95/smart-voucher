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
            const { wallet, website, email } = ctx.request.body

            if (!wallet) throw { message: texts.missing_webshop }
            if (!website) throw { message: texts.missing_website }
            if (!email) throw { message: texts.missing_email }

            if (!strapi.hook.web3Controller.isAddress(wallet)) throw { message: texts.invalid_webshop }
            if (!strapi.hook.helpers.isValidWebsite(website)) throw { message: texts.invalid_website }
            if (!strapi.hook.helpers.isValidEmail(email)) throw { message: texts.invalid_email }

            const webshopByWallet = await strapi.services.webshop.findOne({ wallet })
            if (webshopByWallet) throw { message: texts.webshop_exists }

            const webshopByWebsite = await strapi.services.webshop.findOne({ website })
            if (webshopByWebsite) throw { message: texts.website_exists }

            const webshopByEmail = await strapi.services.webshop.findOne({ email })
            if (webshopByEmail) throw { message: texts.email_exists }

            const entity = await strapi.services.webshop.create({ wallet, website, email })

            // Send 200 `ok`
            return sanitizeEntity(entity, { model: strapi.models.webshop })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async findOne(ctx) {
        try {
            // User id is wallet
            const { id } = ctx.params
            if (!strapi.hook.web3Controller.isAddress(id)) throw { message: texts.invalid_webshop }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': id })

            // Validate webshop exist
            if (!webshop) {
                throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
                throw { message: texts.blocked_webshop }
            }

            // Get webshop data from smart contract
            const { nonce, vouchersCount } = await strapi.hook.web3Controller.getWebshopData(id)

            const data = {
                ...webshop,
                nonce,
                vouchersCount
            }

            // Send 200 `ok`
            return sanitizeEntity(data, { model: strapi.models.webshop })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async addPartner(ctx) {
        try {
            const { webshopAddr, partnerAddr, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: texts.missing_webshop }
            if (!partnerAddr) throw { message: texts.missing_partner }
            if (!nonce) throw { message: texts.missing_nonce }
            if (!signature) throw { message: texts.missing_signature }

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: texts.invalid_webshop }
            if (!strapi.hook.web3Controller.isAddress(partnerAddr)) throw { message: texts.invalid_partner }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: texts.invalid_signature }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })

            // Validate webshop exist
            if (!webshop) {
                throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
                throw { message: texts.blocked_webshop }
            }

            // Find partner
            const partner = await strapi.services.webshop.findOne({ 'wallet': partnerAddr })

            // Validate partner exist
            if (!partner) {
                throw { message: texts.partner_not_exists }
            } else if (partner.blocked) {
                throw { message: texts.blocked_partner }
            }

            await strapi.hook.web3Controller.addPartner({webshopAddr, partnerAddr, nonce, signature})

            const updatedPartners = JSON.stringify({ publishers: [...webshop.publishers, partner.id] })
            await strapi.services.webshop.update({ id: webshop.id }, updatedPartners)

            ctx.send({ ok: true })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async removePartner(ctx) {
        try {
            const { webshopAddr, partnerAddr, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: texts.missing_webshop }
            if (!partnerAddr) throw { message: texts.missing_partner }
            if (!nonce) throw { message: texts.missing_nonce }
            if (!signature) throw { message: texts.missing_signature }

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: texts.invalid_webshop }
            if (!strapi.hook.web3Controller.isAddress(partnerAddr)) throw { message: texts.invalid_partner }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: texts.invalid_signature }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })

            // Validate webshop exist
            if (!webshop) {
              throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
              throw { message: texts.blocked_webshop }
            }

            // Find partner
            const partner = await strapi.services.webshop.findOne({ 'wallet': partnerAddr })

            // Validate partner exist (can be removed even if partner is blocked)
            if (!partner) {
              throw { message: texts.partner_not_exists }
            }

            await strapi.hook.web3Controller.removePartner({webshopAddr, partnerAddr, nonce, signature})

            let updatedPartners = webshop.publishers.filter(el => el.id !== partner.id)
            updatedPartners = JSON.stringify({ publishers: updatedPartners })
            await strapi.services.webshop.update({ id: webshop.id }, publishers)

            ctx.send({ ok: true })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },
}
