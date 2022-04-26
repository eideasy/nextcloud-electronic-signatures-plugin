<script>
import { generateUrl } from '@nextcloud/router';
import SettingsSection from './SettingsSection';
import SettingsGroup from './SettingsGroup';
import SettingsTextInput from './SettingsTextInput';
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect';
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch';
import isoLanguages from './isoLanguages';

export default {
  name: 'AppAdmin',
  components: {
    SettingsSection,
    SettingsGroup,
    SettingsTextInput,
    CheckboxRadioSwitch,
    Multiselect,
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
    <SettingsSection :title="$t($globalConfig.appId, 'eID Easy credentials')">
      <template #settingsHint>
        <p>
          {{
            $t($globalConfig.appId, 'You can find your credentials under the "My Webpages" section on your dashboard at: ')
          }}
          <a
              class="link"
              target="_blank"
              href="https://id.eideasy.com/">
            id.eideasy.com
          </a>
        </p>
        <p>
          {{ $t($globalConfig.appId, 'Your application url is: ') }} <b>{{ instanceUrl }}</b><br>
          {{
            $t($globalConfig.appId, 'Ensure that in your eID Easy panel under "My Websites", you have added the following notification hook to your website: ')
          }} <b>{{ fetchSignedFileUrl }}</b>
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
          <Multiselect
              v-model="apiLanguage"
              :options="apiLanguageOptions"
              track-by="code"
              label="name"
              @change="(option) => slotProps.saveSetting({api_language: option.code})" />
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
            <CheckboxRadioSwitch
                :checked.sync="containerType"
                value="pdf"
                name="container_type_radio"
                type="radio"
                @update:checked="onFileTypeToggle(slotProps.saveSetting)">
              {{ $t($globalConfig.appId, '.pdf') }}
            </CheckboxRadioSwitch>
          </div>
          <div class="radioRow">
            <CheckboxRadioSwitch
                :checked.sync="containerType"
                value="asice"
                name="container_type_radio"
                type="radio"
                @update:checked="onFileTypeToggle(slotProps.saveSetting)">
              {{ $t($globalConfig.appId, '.asice') }}
            </CheckboxRadioSwitch>
            <a href="#" class="infoTip">
              <span class="icon icon-details" />
            </a>
            <div>
              {{
                $t($globalConfig.appId, '.asice files can be opened and verified with the DigiDoc4 application that is available at:')
              }}
              <ul>
                <li>
                  {{ $t($globalConfig.appId, 'Windows - ') }}
                  <a
                      href="https://www.microsoft.com/en-us/p/digidoc4-client/9pfpfk4dj1s6"
                      target="_blank">
                    https://www.microsoft.com/en-us/p/digidoc4-client/9pfpfk4dj1s6
                  </a>
                </li>
                <li>
                  {{ $t($globalConfig.appId, 'macOS - ') }}
                  <a
                      href="https://apps.apple.com/us/app/digidoc4-client/id1370791134"
                      target="_blank">
                    https://apps.apple.com/us/app/digidoc4-client/id1370791134
                  </a>
                </li>
                <li>
                  {{ $t($globalConfig.appId, 'or alternatively - ') }}
                  <a
                      href="https://www.id.ee/en/article/install-id-software/"
                      target="_blank">
                    https://www.id.ee/en/article/install-id-software
                  </a>
                </li>
              </ul>
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
          <CheckboxRadioSwitch
              :checked.sync="signingMode"
              value="remote"
              name="signing_mode_radio"
              type="radio"
              @update:checked="onFileHandlingToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, 'Remote with eID Easy') }}
          </CheckboxRadioSwitch>
          <p>
            {{
              $t($globalConfig.appId, 'With remote signing, the files are sent to the eID Easy server. The signer will go to a signing page on the eID Easy site, where they will be guided through the signing process.')
            }}
          </p>

          <CheckboxRadioSwitch
              :checked.sync="signingMode"
              value="remote_legacy"
              name="signing_mode_radio"
              type="radio"
              @update:checked="onFileHandlingToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, 'Old version of remote with eID Easy') }}
          </CheckboxRadioSwitch>
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

          <CheckboxRadioSwitch
              :checked.sync="signingMode"
              value="local"
              name="signing_mode_radio"
              type="radio"
              @update:checked="onFileHandlingToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, 'Local') }}
          </CheckboxRadioSwitch>
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
          <CheckboxRadioSwitch
              :checked.sync="enableSandbox"
              type="switch"
              @update:checked="onSandboxToggle(slotProps.saveSetting)">
            {{ $t($globalConfig.appId, 'Enable sandbox mode') }}
          </CheckboxRadioSwitch>
        </template>
      </SettingsGroup>
      <p>
        {{ $t($globalConfig.appId, 'While in sandbox mode, you can authenticate and sign with:') }}
      </p>
      <ul>
        <li>
          {{ $t($globalConfig.appId, 'Mobile ID') }}
          <a
              class="link"
              href="https://github.com/SK-EID/MID/wiki/Test-number-for-automated-testing-in-DEMO"
              target="_blank">
            {{ $t($globalConfig.appId, 'test numbers') }}
          </a>
        </li>
        <li>
          {{ $t($globalConfig.appId, 'Your own Mobile ID, if you whitelist it beforehand at ') }}
          <a
              class="link"
              href="https://demo.sk.ee/MIDCertsReg/"
              target="_blank">
            {{ $t($globalConfig.appId, 'https://demo.sk.ee/') }}
          </a>
        </li>
        <li>
          {{ $t($globalConfig.appId, 'Smart ID') }}
          <a
              class="link"
              href="https://github.com/SK-EID/smart-id-documentation/wiki/Environment-technical-parameters#accounts"
              target="_blank">
            {{ $t($globalConfig.appId, 'test numbers') }}
          </a>
        </li>
        <li>
          {{
            $t($globalConfig.appId, 'Production ID card from any of our supported countries (does not work for signing asice/bdoc containers),')
          }}
        </li>
        <li>
          {{
            $t($globalConfig.appId, 'Estonian test ID-card (or any other supported country). More info regarding Estonian test ID-cards can be found on')
          }}
          <a
              class="link"
              href="https://www.id.ee/en/article/service-testing-general-information-2/"
              target="_blank">
            {{ $t($globalConfig.appId, 'SK’s site.') }}
          </a>
        </li>
      </ul>
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
