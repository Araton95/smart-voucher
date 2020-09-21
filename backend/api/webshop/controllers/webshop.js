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

            return  ctx.badRequest(null, message)
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

            return  ctx.badRequest(null, message)
        }
    },

    async addPartners(ctx) {
        try {
            const { webshopAddr, partners, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: texts.missing_webshop }
            if (!partners) throw { message: texts.missing_partners }
            if (!nonce) throw { message: texts.missing_nonce }
            if (!signature) throw { message: texts.missing_signature }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: texts.invalid_signature }
            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: texts.invalid_webshop }

            for (let index = 0; index < partners.length; index++) {
                const partnerAddr = partners[index]
                if (!strapi.hook.web3Controller.isAddress(partnerAddr)) throw { message: `${texts.invalid_partner}: ${partnerAddr}` }
            }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })

            // Validate webshop exist
            if (!webshop) {
                throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
                throw { message: texts.blocked_webshop }
            }

            // Send tx to smart contract
            await strapi.hook.web3Controller.addPartners({webshopAddr, partners, nonce, signature})

            // Find partners
            const dbPartners = await strapi.services.webshop.find({ 'wallet': partners })
            const dbPartnersIds = dbPartners.map(p => p.id)

            const updatedPartners = JSON.stringify({ publishers: [...webshop.publishers, ...dbPartnersIds] })
            await strapi.services.webshop.update({ id: webshop.id }, updatedPartners)

            ctx.send({ ok: true })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return  ctx.badRequest(null, message)
        }
    },

    async removePartners(ctx) {
        try {
            const { webshopAddr, partners, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: texts.missing_webshop }
            if (!partners) throw { message: texts.missing_partners }
            if (!nonce) throw { message: texts.missing_nonce }
            if (!signature) throw { message: texts.missing_signature }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: texts.invalid_signature }
            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: texts.invalid_webshop }

            for (let index = 0; index < partners.length; index++) {
                const partnerAddr = partners[index]
                if (!strapi.hook.web3Controller.isAddress(partnerAddr)) throw { message: `${texts.invalid_partner}: ${partnerAddr}` }
            }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })

            // Validate webshop exist
            if (!webshop) {
              throw { message: texts.webshop_not_exists }
            } else if (webshop.blocked) {
              throw { message: texts.blocked_webshop }
            }

            await strapi.hook.web3Controller.removePartners({webshopAddr, partners, nonce, signature})

            // Find partners
            const dbPartners = await strapi.services.webshop.find({ 'wallet': partners })
            const dbPartnersIds = dbPartners.map(p => p.id)

            const updatedPartners = webshop.publishers.filter(el => !dbPartnersIds.includes(el.id))
            let updatePartnersIds = updatedPartners.map(p => p.id)
            updatePartnersIds = JSON.stringify({ publishers: updatePartnersIds })

            await strapi.services.webshop.update({ id: webshop.id }, updatePartnersIds)

            ctx.send({ ok: true })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return  ctx.badRequest(null, message)
        }
    },
}
