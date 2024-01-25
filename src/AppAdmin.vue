<script>
import { generateUrl } from '@nextcloud/router';
import SettingsSection from './SettingsSection.vue';
import SettingsGroup from './SettingsGroup.vue';
import SettingsTextInput from './SettingsTextInput.vue';
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js';
import isoLanguages from './isoLanguages';
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js';

export default {
  name: 'AppAdmin',
  components: {
    SettingsSection,
    SettingsGroup,
    SettingsTextInput,
    NcCheckboxRadioSwitch,
    NcSelect,
    NcNoteCard,
  },
  data() {
    return {
      clientId: '',
      secret: '',
      clientIdPlaceholder: this.$parent.clientId,
      secretPlaceholder: this.$parent.secret,
      allowSimpleSignatures: this.$parent.enableOtp,
      signingMode: this.$parent.signingMode || 'remote',
      padesUrl: this.$parent.padesUrl,
      enableSandbox: !!this.$parent.enableSandbox,
      containerType: this.$parent.containerType,
      showAdvancedSettings: false,
      apiLanguage: isoLanguages.getByCode(this.$parent.apiLanguage),
      apiLanguageOptions: isoLanguages.getAll(),
      remoteSigningQueueStatusWebhook: this.$parent.remoteSigningQueueStatusWebhook,
    };
  },
  computed: {
    fetchSignedFileUrl() {
      return window.location.origin + this.generateNextcloudUrl('/apps/electronicsignatures/fetch_signed_file');
    },
    instanceUrl() {
      return window.location.origin;
    },
    simpleSignaturesSettingIsDisabled() {
      // simple sigs are only supported for remote_legacy
      return this.signingMode === 'local' || this.signingMode === 'remote' || this.containerType === 'asice';
    },
    buttonTextShowAdvanced() {
      if (this.showAdvancedSettings) {
        return this.$t(this.$globalConfig.appId, 'Hide advanced settings');
      } else {
        return this.$t(this.$globalConfig.appId, 'Show advanced settings');
      }
    },
  },
  methods: {
    generateNextcloudUrl(url) {
      return generateUrl(url);
    },
    onFileHandlingToggle(saveSetting) {
      const enableLocalSigning = this.signingMode === 'local';

      const settings = { signing_mode: this.signingMode };
      if (enableLocalSigning) {
        this.allowSimpleSignatures = '0';
        settings.enable_otp = false;
      }
      saveSetting(settings);
    },
    onFileTypeToggle(saveSetting) {
      const settings = {
        container_type: this.containerType,
      };
      if (this.containerType === 'asice') {
        this.allowSimpleSignatures = '0';
        settings.enable_otp = false;
      }
      saveSetting(settings);
    },
    onSandboxToggle(saveSetting) {
      saveSetting({
        enable_sandbox: this.enableSandbox,
      });
    },
  },
};
</script>

<template>
  <div>
    <SettingsSection v-if="signingMode !== 'remote'">
      <NcNoteCard type="error">
        <p>
          {{
            $t($globalConfig.appId, 'Your are using a deprecated signing mode.')
          }}
        </p>
        <p>
          {{
            $t($globalConfig.appId, 'Please scroll down and click on "Show advanced settings."')
          }}
        </p>
        <p>
          {{
            $t($globalConfig.appId, 'Under advanced settings, enable the "Remote with eID Easy" option.')
          }}
        </p>
      </NcNoteCard>
    </SettingsSection>

    <SettingsSection :title="$t($globalConfig.appId, 'eID Easy credentials')">
      <template #settingsHint>
        <p>
          {{
            "1. " + $t($globalConfig.appId, 'Head over to eID Easy sign-up page and authenticate yourself: ')
          }}
          <a
              class="link"
              target="_blank"
              href="https://id.eideasy.com">
            https://id.eideasy.com
          </a>
        </p>
        <p>
          {{
            "2. " + $t($globalConfig.appId, 'Navigate to: ')
          }}
          <a
              class="link"
              target="_blank"
              href="https://id.eideasy.com/admin/clientlist">
            My Webpages
          </a>
        </p>
        <p>
          {{
            "3. " + $t($globalConfig.appId, 'Click on the "Add webpage" button (on the right)')
          }}
        </p>
        <p>
          {{
            "4. " + $t($globalConfig.appId, 'For the "Website landing page address" field enter: ')
          }}
          <b>{{ instanceUrl }}</b>
        </p>
        <p>
          {{
            "5. " + $t($globalConfig.appId, 'Click the "Register" button')
          }}
        </p>
        <p>
          {{
            "6. " + $t($globalConfig.appId, 'Scroll to the "Notification hooks" section and for the "Signature notification URL" field enter: ')
          }}
          <b>{{ fetchSignedFileUrl }}</b>
        </p>
        <p>
          {{
            "7. " + $t($globalConfig.appId, 'Click the "Save hooks data" button')
          }}
        </p>
        <p>
          {{
            "8. " + $t($globalConfig.appId, 'On the left sidebar, click again on ')
          }}
          <a
              class="link"
              target="_blank"
              href="https://id.eideasy.com/admin/clientlist">
            My Webpages
          </a>
        </p>
        <p>
          {{
            "9. " + $t($globalConfig.appId, 'You can now find your Client ID and Secret in the "client_id/secret" column. Use these to fill in the fields below:')
          }}
        </p>
      </template>
      <SettingsGroup>
        <template v-slot:default="slotProps">
          <div>
            <SettingsTextInput
                v-model="clientId"
                :placeholder="clientIdPlaceholder"
                :on-button-click="() => slotProps.saveSetting({client_id: clientId})">
              <template #label>
                {{ $t($globalConfig.appId, 'Client ID') }}
              </template>
            </SettingsTextInput>
          </div>
          <div>
            <SettingsTextInput
                v-model="secret"
                :placeholder="secretPlaceholder"
                :on-button-click="() => slotProps.saveSetting({secret})">
              <template #label>
                {{ $t($globalConfig.appId, 'Secret') }}
              </template>
            </SettingsTextInput>
          </div>
        </template>
      </SettingsGroup>
    </SettingsSection>

    <SettingsSection :title="$t($globalConfig.appId, 'Advanced settings')">
      <template #settingsHint>
        <p>
          {{ $t($globalConfig.appId, 'Only change these settings if you know what you are doing.') }}
        </p>
      </template>
      <button @click.prevent="showAdvancedSettings = !showAdvancedSettings">
        {{ buttonTextShowAdvanced }}
      </button>
    </SettingsSection>

    <SettingsSection v-if="showAdvancedSettings" :title="$t($globalConfig.appId, 'eID Easy service language')">
      <template #settingsHint>
        <p>
          {{ $t($globalConfig.appId, 'Choose the language for eID Easy signing views and emails that the end users receive.') }}
        </p>
      </template>

      <SettingsGroup>
        <template v-slot:default="slotProps">
          <NcSelect
              v-model="apiLanguage"
              :options="apiLanguageOptions"
              track-by="code"
              label="name"
              @input="(option) => slotProps.saveSetting({api_language: option.code})" />
        </template>
      </SettingsGroup>
    </SettingsSection>

    <SettingsSection v-if="showAdvancedSettings" :title="$t($globalConfig.appId, 'Output file type for pdf')">
      <template #settingsHint>
        <p>
          {{ $t($globalConfig.appId, 'These settings only apply to pdf files.') }}
        </p>
        <p>
          {{ $t($globalConfig.appId, 'If you choose .pdf as the output file type, then your final signed file will be a pdf.') }}
          {{ $t($globalConfig.appId, 'If you choose .asice, then your final signed file will be an .asice file that contains the original pdf file.') }}
        </p>
      </template>

      <SettingsGroup>
        <template v-slot:default="slotProps">
          <div class="radioRow">
            <NcCheckboxRadioSwitch
                :checked.sync="containerType"
                value="pdf"
                name="container_type_radio"
                type="radio"
                @update:checked="onFileTypeToggle(slotProps.saveSetting)">
              {{ $t($globalConfig.appId, '.pdf') }}
            </NcCheckboxRadioSwitch>
          </div>
          <div class="radioRow">
            <NcCheckboxRadioSwitch
                :checked.sync="containerType"
                value="asice"
                name="container_type_radio"
                type="radio"
                @update:checked="onFileTypeToggle(slotProps.saveSetting)">
              {{ $t($globalConfig.appId, '.asice') }}
            </NcCheckboxRadioSwitch>
            <a href="#" class="infoTip">
              <span class="icon icon-details" />
            </a>
            <div>
              {{
                $t($globalConfig.appId, '.asice files can be opened and verified with the DigiDoc4 application that is available at:')
              }}
              <a
                  href="https://www.id.ee/en/article/install-id-software/"
                  target="_blank">
                https://www.id.ee/en/article/install-id-software
              </a>
            </div>
          </div>
        </template>
      </SettingsGroup>
    </SettingsSection>

    <SettingsSection v-if="showAdvancedSettings" :title="$t($globalConfig.appId, 'File handling')">
      <template #settingsHint>
        <p>
          {{ $t($globalConfig.appId, 'These settings determine how and where the files are signed.') }}
        </p>
      </template>
      <SettingsGroup>
        <template v-slot:default="slotProps">
          <NcCheckboxRadioSwitch
              :checked.sync="signingMode"
              value="remote"
              name="signing_mode_radio"
              type="radio"
              @update:checked="onFileHandlingToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, 'Remote with eID Easy') }}
          </NcCheckboxRadioSwitch>
          <p>
            {{
              $t($globalConfig.appId, 'With remote signing, the files are sent to the eID Easy server. The signer will go to a signing page on the eID Easy site, where they will be guided through the signing process.')
            }}
          </p>

          <div class="subSection">
            <SettingsTextInput
                v-model="remoteSigningQueueStatusWebhook"
                :placeholder="remoteSigningQueueStatusWebhook"
                :on-button-click="() => slotProps.saveSetting({remote_signing_queue_status_webhook: remoteSigningQueueStatusWebhook || 'reset'})">
              <template #label>
                {{ $t($globalConfig.appId, 'Queue status webhook url') }}
              </template>
            </SettingsTextInput>
            <p>
              {{ $t($globalConfig.appId, 'You probably only need to fill this in if your Nextcloud instance is behind a reverse proxy etc. This is the url to where eID Easy will send the signing queue status updates. Keep in mind that this URL has to be accessible over the public internet.') }}
            </p>
          </div>

          <NcCheckboxRadioSwitch
              :checked.sync="signingMode"
              value="remote_legacy"
              name="signing_mode_radio"
              type="radio"
              @update:checked="onFileHandlingToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, '[Deprecated!] Old version of remote with eID Easy') }}
          </NcCheckboxRadioSwitch>
          <NcNoteCard v-if="signingMode === 'remote_legacy'" type="warning">
            <p>
              {{
                $t($globalConfig.appId, 'This signing mode is deprecated. Please enable "Remote with eID Easy"')
              }}
            </p>
          </NcNoteCard>
          <p>
            {{
              $t($globalConfig.appId, 'With remote signing, the files are sent to the eID Easy server. The signer will go to a signing page on the eID Easy site, where they will be guided through the signing process.')
            }}
          </p>

          <p v-if="simpleSignaturesSettingIsDisabled">
            "Allow simple signatures" setting is only available if ".pdf" is selected in "Output file type for pdf" setting.
          </p>

          <div :class="`subSection ${simpleSignaturesSettingIsDisabled ? 'disabled' : ''}`">
            <div class="checkboxWrap">
              <input
                  id="allowOnlyEmail"
                  v-model="allowSimpleSignatures"
                  type="checkbox"
                  class="checkbox"
                  :disabled="simpleSignaturesSettingIsDisabled"
                  true-value="1"
                  false-value="0"
                  @change="slotProps.saveSetting({enable_otp: allowSimpleSignatures === '1'})">
              <label for="allowOnlyEmail">
                {{ $t($globalConfig.appId, 'Allow simple signatures.') }}
              </label>
            </div>
            <p>
              {{
                $t($globalConfig.appId, 'Simple signatures are generated by sending a unique signing link to the signer\'s e-mail address or phone number. When the user clicks the link and expresses their consent, they are considered to have signed the document. A cryptographic e-seal will be added to the document to ensure that the document is not modified after it was accepted by the signer. Simple signatures are easier to use, but provide lower legal certainty compared to Qualified Electronic Signatures.')
              }}
            </p>
            <p>
              <b>{{ $t($globalConfig.appId, 'Please note that:') }}</b>
            </p>
            <ul>
              <li>
{{
                  $t($globalConfig.appId, 'Simple Electronic Signatures are always collected remotely, in order to increase the legal value of the signature.')
                }}
              </li>
              <li>
                {{ $t($globalConfig.appId, 'Simple Electronic Signatures work with pdf files only.') }}
              </li>
            </ul>
          </div>

          <NcCheckboxRadioSwitch
              :checked.sync="signingMode"
              value="local"
              name="signing_mode_radio"
              type="radio"
              @update:checked="onFileHandlingToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, 'Local') }}
          </NcCheckboxRadioSwitch>
          <NcNoteCard v-if="signingMode === 'local'" type="warning">
            <p>
              {{
                $t($globalConfig.appId, 'This signing mode is deprecated. Please enable "Remote with eID Easy"')
              }}
            </p>
          </NcNoteCard>
          <p>
            {{
              $t($globalConfig.appId, 'With local signing, the signer is directed to your Nextcloud instance for the signing process. They will not need an account in your Nextcloud instance. The file contents are not sent to the eID Easy server, however the file names and signatory names will pass through eID Easy server, to enable electronic signature creation.')
            }}
          </p>
          <p>
            {{
              $t($globalConfig.appId, 'Note: local signing supports Finnish, Portuguese, Estonian, Latvian and Lithuanian id card based signatures. We are continuously adding new signing methods. Please let us know at support@eideasy.com if there are any signing methods you\'d like us to add next and we will happily prioritize them.')
            }}
          </p>
          <p>
            {{
              $t($globalConfig.appId, 'To enable local signing for pdf containers, you must set up a PADES service on your server. To do this:')
            }}
          </p>
          <ol>
            <li>{{ $t($globalConfig.appId, 'Install docker') }}</li>
            <li>
{{
                $t($globalConfig.appId, 'Pull the service container into the directory of your choice: docker pull eideasy/pades-external-digital-signatures')
              }}
            </li>
            <li>cd eideasy-external-pades-digital-signatures/</li>
            <li>
{{ $t($globalConfig.appId, 'Start the container: ') }}<span v-pre>sudo docker run -p 8080:8084 --name=eideasy_detached_pades --restart always --log-driver syslog --log-opt tag="{{ .Name }}/{{ .ID }}" eideasy/pades-external-digital-signatures</span>
            </li>
            <li>
{{
                $t($globalConfig.appId, 'Provide the container\'s url for the PADES URL setting below. If you didn\'t change the above "docker run" command, the url is 0.0.0.0:8080.')
              }}
            </li>
          </ol>
        </template>
      </SettingsGroup>
      <SettingsGroup>
        <template v-slot:default="slotProps">
          <SettingsTextInput
              v-model="padesUrl"
              :placeholder="padesUrl"
              :on-button-click="() => slotProps.saveSetting({pades_url: padesUrl})">
            <template #label>
              {{ $t($globalConfig.appId, 'PADES URL') }}
            </template>
          </SettingsTextInput>
        </template>
      </SettingsGroup>
    </SettingsSection>

    <SettingsSection v-if="showAdvancedSettings" :title="$t($globalConfig.appId, 'Sandbox mode')">
      <template #settingsHint>
        <p>
          {{ $t($globalConfig.appId, 'You can use the sandbox mode to test out our service free of charge.') }}
        </p>
        <p>
          {{ $t($globalConfig.appId, 'Sign up here:') }}
          <a href="https://test.eideasy.com/" target="_blank" class="link">https://test.eideasy.com/</a>
          {{ $t($globalConfig.appId, 'to get the eID Easy credentials for the sandbox mode.') }}
        </p>
      </template>
      <SettingsGroup>
        <template v-slot:default="slotProps">
          <NcCheckboxRadioSwitch
              :checked.sync="enableSandbox"
              type="switch"
              @update:checked="onSandboxToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, 'Enable sandbox mode') }}
          </NcCheckboxRadioSwitch>
        </template>
      </SettingsGroup>
      <p>
        {{ $t($globalConfig.appId, 'While in sandbox mode, you can use the following test users: ') }}
      </p>
      <a
          class="link"
          href="https://docs.eideasy.com/guide/test-environment.html#electronic-identities-in-test-environment"
          target="_blank">
        {{'https://docs.eideasy.com/guide/test-environment.html#electronic-identities-in-test-environment' }}
      </a>
    </SettingsSection>
  </div>
</template>

<style scoped>
.link {
  color: #0082c9;
}

.subSection {
  padding: 8px 0 30px 30px;
}

.disabled {
  opacity: 0.4;
}

p {
  margin-bottom: 10px;
}

ol {
  list-style: none;
  counter-reset: list-item-counter;
}

ol li {
  counter-increment: list-item-counter;
}

ol li::before {
  content: counter(list-item-counter) '. ';
}

li {
  display: block;
  position: relative;
  padding-left: 16px;
}

li + li {
  margin-top: 6px;
}

li:before {
  content: '•';
  display: block;
  position: absolute;
  left: 0;
  top: 0;
}

</style>
