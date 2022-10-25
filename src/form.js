// SPDX-FileCopyrightText: 2022 Bruno Alfred <hello@brunoalfred.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

import Vue from 'vue'
import RegistrationPhone from './components/RegistrationPhone.vue'
import Verification from './components/Verification.vue'
import User from './components/User.vue'

Vue.prototype.t = t
Vue.prototype.OC = OC

let view = null
if (document.getElementById('registration_email')) {
	view = new Vue({
		el: '#registration_email',
		render: h => h(RegistrationPhone),
	})
}

if (document.getElementById('registration_verification')) {
	view = new Vue({
		el: '#registration_verification',
		render: h => h(Verification),
	})
}

if (document.getElementById('registration_user')) {
	view = new Vue({
		el: '#registration_user',
		render: h => h(User),
	})
}

export default view
