<!--
  - @copyright Copyright (c) 2022 Bruno Alfred <hello@brunoalfred.me>
  -
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
		<form action="" method="post">
			<fieldset>
				<NcNoteCard v-if="message !== ''" type="error">
					{{ message }}
				</NcNoteCard>

				<NcTextField name="phone"
					type="phone"
					:label="phoneLabel"
					:label-visible="true"
					required
					autofocus>
					<Phone :size="20" />
				</NcTextField>

				<div id="terms_of_service" />

				<input type="hidden" name="requesttoken" :value="requesttoken">
				<NcButton id="submit"
					native-type="submit"
					type="primary"
					:wide="true">
					{{ submitValue }}
				</NcButton>

				<NcButton type="tertiary"
					:href="loginFormLink"
					:wide="true">
					{{ t('registration', 'Back to login') }}
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
import { loadState } from '@nextcloud/initial-state'
import Phone from 'vue-material-design-icons/Phone.vue'

export default {
	name: 'RegistrationPhone',

	components: {
		NcButton,
		NcTextField,
		NcNoteCard,
		Phone,
	},

	data() {
		return {
			message: loadState('twigacloudsignup', 'message'),
			requesttoken: getRequestToken(),
			isLoginFlow: loadState('twigacloudsignup', 'isLoginFlow'),
			loginFormLink: loadState('twigacloudsignup', 'loginFormLink'),
		}
	},

	computed: {
		phoneLabel() {
			return t('twigacloudsignup', 'Phone')
		},
		submitValue() {
			if (this.isLoginFlow) {
				return t('twigacloudsignup', 'Request verification code')
			} else {
				return t('twigacloudsignup', 'Request verification link')
			}
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
