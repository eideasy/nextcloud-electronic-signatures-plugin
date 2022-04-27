const languages = Object.freeze([
  {
    code: 'cs',
    name: 'Czech',
  },
  {
    code: 'en',
    name: 'English',
  },
  {
    code: 'et',
    name: 'Estonian',
  },
  {
    code: 'fi',
    name: 'Finnish',
  },
  {
    code: 'fr',
    name: 'French',
  },
  {
    code: 'de',
    name: 'German',
  },
  {
    code: 'it',
    name: 'Italian',
  },
  {
    code: 'lv',
    name: 'Latvian',
  },
  {
    code: 'lt',
    name: 'Lithuanian',
  },
  {
    code: 'no',
    name: 'Norwegian',
  },
  {
    code: 'pt',
    name: 'Portuguese',
  },
  {
    code: 'ru',
    name: 'Russian',
  },
  {
    code: 'sk',
    name: 'Slovak',
  },
  {
    code: 'es',
    name: 'Spanish',
  },
  {
    code: 'sv',
    name: 'Swedish',
  },
]);

class IsoLanguages {

  constructor(languages) {
    this.languages = languages;
  }

  getByCode(code) {
    return this.languages.find(item => item.code === code);
  }

  getAll() {
    return languages;
  }

}

const isoLanguages = new IsoLanguages(languages);

export default isoLanguages;
