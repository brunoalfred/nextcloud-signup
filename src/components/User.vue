<!--
  - @copyright Copyright (c) 2022 Bruno Alfred <hello@brunoalfred.me>
  -
  - @author Bruno Alfred <hello@brunoalfred.me>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<div class="guest-box">
		<form action="" method="post" @submit="onSubmit">
			<input type="hidden" name="requesttoken" :value="requesttoken">
			<fieldset>
				<NcNoteCard v-if="message !== ''" type="error">
					{{ message }}
				</NcNoteCard>
				<p v-else>
					{{ t('twigacloudsignup', 'Welcome, you can create your account below.') }}
				</p>

				<NcNoteCard v-if="additionalHint" type="success">
					{{ additionalHint }}
				</NcNoteCard>

				<NcTextField v-if="phone.length > 0"
					:value.sync="phone"
					type="phone"
					:label="t('twigacloudsignup', 'phone')"
					:label-visible="true"
					name="phone"
					disabled>
					<Phone :size="20" class="input__icon" />
				</NcTextField>

				<NcTextField :value.sync="loginname"
					type="text"
					name="loginname"
					:label="t('twigacloudsignup', 'Login name')"
					:label-visible="true"
					required>
					<Key :size="20" class="input__icon" />
				</NcTextField>

				<NcTextField v-if="showFullname"
					:value.sync="fullname"
					type="text"
					name="fullname"
					:label="t('twigacloudsignup', 'Full name')"
					:label-visible="true"
					:required="enforceFullname">
					<Account :size="20" class="input__icon" />
				</NcTextField>
				<input v-else
					type="hidden"
					name="fullname"
					value="">

				<NcTextField v-if="showPhone"
					:value.sync="phone"
					type="text"
					name="phone"
					:label="t('twigacloudsignup', 'Phone number')"
					:label-visible="true"
					:required="enforcePhone">
					<Phone :size="20" class="input__icon" />
				</NcTextField>
				<input v-else
					type="hidden"
					name="phone"
					value="">

				<NcPasswordField :value.sync="password"
					:label="t('twigacloudsignup', 'Password')"
					:label-visible="true"
					name="password"
					required>
					<Lock :size="20" class="input__icon" />
				</NcPasswordField>

				<NcButton id="submit"
					native-type="submit"
					type="primary"
					:wide="true"
					:disabled="submitting || password.length === 0">
					{{ submitting ? t('twigacloudsignup', 'Loading') : t('twigacloudsignup', 'Create account') }}
				</NcButton>
			</fieldset>
		</form>
	</div>
</template>

<script>
import { getRequestToken } from '@nextcloud/auth'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import { loadState } from '@nextcloud/initial-state'
import Lock from 'vue-material-design-icons/Lock.vue'
import Phone from 'vue-material-design-icons/Phone.vue'
import Account from 'vue-material-design-icons/Account.vue'
import Key from 'vue-material-design-icons/Key.vue'

export default {
	name: 'User',

	components: {
		NcButton,
		NcNoteCard,
		NcTextField,
		NcPasswordField,
		Lock,
		Phone,
		Account,
		Key,
	},

	data() {
		return {
			phone: loadState('twigacloudsignup', 'phone'),
			loginname: loadState('twigacloudsignup', 'loginname'),
			fullname: loadState('twigacloudsignup', 'fullname'),
			showFullname: loadState('twigacloudsignup', 'showFullname'),
			enforceFullname: loadState('twigacloudsignup', 'enforceFullname'),
			message: loadState('twigacloudsignup', 'message'),
			password: loadState('twigacloudsignup', 'password'),
			additionalHint: loadState('twigacloudsignup', 'additionalHint'),
			requesttoken: getRequestToken(),
			loginFormLink: loadState('twigacloudsignup', 'loginFormLink'),
			isPasswordHidden: true,
			passwordInputType: 'password',
			submitting: false,
		}
	},

	methods: {
		togglePassword() {
			if (this.passwordInputType === 'password') {
				this.passwordInputType = 'text'
			} else {
				this.passwordInputType = 'password'
			}
		},
		onSubmit() {
			// prevent sending the request twice
			this.submitting = true
		},
	},
}
</script>

<style lang="scss" scoped>
.guest-box {
    text-align: left;
}

fieldset {
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

.button-vue--vue-tertiary {
    box-sizing: border-box;
}
</style>
