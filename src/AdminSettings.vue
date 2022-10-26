<!--
  - @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
  -
  - @author Roeland Jago Douma <roeland@famdouma.nl>
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
	<div id="registration_settings_form">
		<NcSettingsSection :title="t('twigacloudsignup', 'Registration settings')">
			<NcCheckboxRadioSwitch :checked.sync="adminApproval"
				type="switch"
				:disabled="loading"
				@update:checked="saveData">
				{{ t('twigacloudsignup', 'Require administrator approval') }}
			</NcCheckboxRadioSwitch>

			<p><em>{{ t('twigacloudsignup', 'Enabling "administrator approval" will prevent registrations from mobile and desktop clients to complete as the credentials cannot be verified by the client until the user was enabled.') }}</em></p>

			<div>
				<div class="margin-top">
					<label for="registered_user_group">
						{{ t('twigacloudsignup', 'Registered users default group') }}
					</label>
				</div>
				<NcMultiselect id="registered_user_group"
					v-model="registeredUserGroup"
					:placeholder="t('twigacloudsignup', 'Select group')"
					:options="groups"
					:disabled="loading"
					:searchable="true"
					:tag-width="60"
					:loading="loadingGroups"
					:allow-empty="true"
					:close-on-select="false"
					track-by="id"
					label="displayname"
					@search-change="searchGroup"
					@change="saveData" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection :title="t('twigacloudsignup', 'User settings')">
			<NcCheckboxRadioSwitch v-if="!emailIsOptional"
				:checked.sync="emailIsLogin"
				type="switch"
				:disabled="loading"
				@update:checked="saveData">
				{{ t('twigacloudsignup', 'Force email as login name') }}
			</NcCheckboxRadioSwitch>
			<template v-if="!emailIsLogin">
				<p>
					<label for="username_policy_regex">{{ t('twigacloudsignup', 'Login name policy') }}</label>
					<input id="username_policy_regex"
						v-model="usernamePolicyRegex"
						type="text"
						name="username_policy_regex"
						:disabled="loading"
						placeholder="E.g.: /^[a-z-]+\.[a-z-]+$/"
						:aria-label="t('twigacloudsignup', 'Regular expression to validate login names')"
						@input="debounceSavingSlow">
				</p>
				<em>{{ t('twigacloudsignup', 'If configured, login names will be validated through the regular expression. If the validation fails the user is prompted with a generic error. Make sure your regex is working correctly.') }}</em>
			</template>

			<NcCheckboxRadioSwitch :checked.sync="showFullname"
				:disabled="loading"
				type="switch"
				@update:checked="saveData">
				{{ t('twigacloudsignup', 'Show full name field') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch v-if="showFullname"
				class="indent"
				:checked.sync="enforceFullname"
				type="switch"
				:disabled="loading"
				@update:checked="saveData">
				{{ t('twigacloudsignup', 'Enforce full name field') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch v-if="canShowPhone"
				:checked.sync="showPhone"
				type="switch"
				:disabled="loading"
				@update:checked="saveData">
				{{ t('twigacloudsignup', 'Show phone field') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch v-if="canShowPhone && showPhone"
				class="indent"
				:checked.sync="enforcePhone"
				type="switch"
				:disabled="loading"
				@update:checked="saveData">
				{{ t('twigacloudsignup', 'Enforce phone field') }}
			</NcCheckboxRadioSwitch>
		</NcSettingsSection>

		<NcSettingsSection :title="t('registration', 'User instructions')"
			:description="t('registration', 'Caution: The user instructions will not be translated and will therefore be displayed as configured below for all users regardless of their actual language.')">
			<h3>{{ t('registration', 'Registration form instructions') }}</h3>
			<input v-model="additionalHint"
				type="text"
				name="additional_hint"
				:disabled="loading"
				placeholder="Please create your username following the scheme 'firstname.lastname'."
				:aria-label="t('registration', 'A short message that is shown to the user in the registration process.')"
				@input="debounceSavingSlow">
			<p><em>{{ t('twigacloudsignup', 'Add additional user instructions (e.g. for choosing their login name). If configured the text is displayed in the account creation step of the registration process.') }}</em></p>

			<h3>{{ t('twigacloudsignup', 'Verification email instructions') }}</h3>
			<input v-model="emailVerificationHint"
				type="text"
				name="email_verification_hint"
				:disabled="loading"
				placeholder="Please create your username following the scheme 'firstname.lastname'."
				:aria-label="t('registration', 'A short message that is shown to the user in the verification email.')"
				@input="debounceSavingSlow">
			<p><em>{{ t('twigacloudsignup', 'Add additional user instructions (e.g. for choosing their login name). If configured the text is embedded in the verification-email.') }}</em></p>
		</NcSettingsSection>
	</div>
</template>

<script>
import NcMultiselect from '@nextcloud/vue/dist/Components/NcMultiselect.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/styles/toast.scss'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import debounce from 'debounce'

export default {
	name: 'AdminSettings',

	components: {
		NcMultiselect,
		NcSettingsSection,
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			loading: false,
			loadingGroups: false,
			groups: [],
			saveNotification: null,

			adminApproval: false,
			registeredUserGroup: '',
			emailIsLogin: false,
			usernamePolicyRegex: '',
			showFullname: false,
			enforceFullname: false,
			canShowPhone: false,
			showPhone: false,
			enforcePhone: false,
			additionalHint: '',
			emailVerificationHint: '',
		}
	},

	computed: {

	},

	mounted() {
		this.adminApproval = loadState('twigacloudsignup', 'admin_approval_required')
		this.registeredUserGroup = loadState('twigacloudsignup', 'registered_user_group')
		this.phoneIsLogin = loadState('twigacloudsignup', 'phone_is_login')
		this.usernamePolicyRegex = loadState('twigacloudsignup', 'username_policy_regex')
		this.showFullname = loadState('twigacloudsignup', 'show_fullname')
		this.enforceFullname = loadState('twigacloudsignup', 'enforce_fullname')
		this.canShowPhone = loadState('twigacloudsignup', 'can_show_phone')
		this.showPhone = loadState('twigacloudsignup', 'show_phone')
		this.enforcePhone = loadState('twigacloudsignup', 'enforce_phone')
		this.additionalHint = loadState('twigacloudsignup', 'additional_hint')
		this.phoneVerificationHint = loadState('twigacloudsignup', 'phone_verification_hint')

		this.searchGroup('')
	},
	methods: {
		debounceSavingSlow: debounce(function() {
			this.saveData()
		}, 2000),

		async saveData() {
			this.loading = true
			if (this.saveNotification) {
				await this.saveNotification.hideToast()
			}

			try {
				const response = await axios.post(generateUrl('/apps/registration/settings'), {
					admin_approval_required: this.adminApproval,
					registered_user_group: this.registeredUserGroup?.id,
					phone_is_login: !this.phoneIsOptional && this.phoneIsLogin,
					username_policy_regex: this.usernamePolicyRegex,
					show_fullname: this.showFullname,
					enforce_fullname: this.enforceFullname,
					show_phone: this.showPhone,
					enforce_phone: this.enforcePhone,
					additional_hint: this.additionalHint,
					phone_verification_hint: this.phoneVerificationHint,
				})

				if (response?.data?.status === 'success' && response?.data?.data?.message) {
					this.saveNotification = showSuccess(response.data.data.message)
				} else if (response?.data?.data?.message) {
					this.saveNotification = showError(response.data.data.message)
				} else {
					this.saveNotification = showError(t('twigacloudsignup', 'An error occurred while saving the settings'))
				}
			} catch (e) {
				if (e.response?.data?.data?.message) {
					this.saveNotification = showError(e.response.data.data.message)
				} else {
					this.saveNotification = showError(t('twigacloudsignup', 'An error occurred while saving the settings'))
					console.error(e)
				}
			}

			this.loading = false
		},

		searchGroup: debounce(async function(query) {
			this.loadingGroups = true
			try {
				const response = await axios.get(generateOcsUrl('cloud/groups/details'), {
					search: query,
					limit: 20,
					offset: 0,
				})
				this.groups = response.data.ocs.data.groups.sort(function(a, b) {
					return a.displayname.localeCompare(b.displayname)
				})
			} catch (err) {
				console.error('Could not fetch groups', err)
			} finally {
				this.loadingGroups = false
			}
		}, 500),
	},
}
</script>

<style scoped lang="scss">

p {
	label {
		display: block;
	}
}

.indent {
	padding-left: 28px;
}

.margin-top {
	margin-top: 1rem;
}

</style>
