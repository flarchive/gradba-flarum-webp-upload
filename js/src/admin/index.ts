import app from 'flarum/admin/app';

const t = (key: string) => app.translator.trans('gradba-webp-upload.admin.settings.' + key);

app.initializers.add('gradba-webp-upload', () => {
  app.extensionData
    .for('gradba-webp-upload')

    .registerSetting({
      label: t('enabled'),
      help: t('enabled-Help'),
      setting: 'gradba-webp-upload.enabled',
      type: 'boolean',
    })
    .registerSetting({
      label: t('quality'),
      help: t('quality-Help'),
      setting: 'gradba-webp-upload.quality',
      type: 'number',
      min: 1,
      max: 100,
    })
    .registerSetting({
      label: t('maxSize'),
      help: t('maxSize-Help'),
      setting: 'gradba-webp-upload.maxSize',
      type: 'number',
      min: 0,
      max: 5000,
    });
});
