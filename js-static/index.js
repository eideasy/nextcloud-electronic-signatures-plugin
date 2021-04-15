alert('test');


var fileActionsPlugin = {
  attach: function (fileList) {
    console.log('fileActionsPlugin');
    fileList.fileActions.registerAction({
      mime: "all",
      name: "Sign",
      displayName: "Get signing url",
      order: -100,
      permissions: 0,
      iconClass: 'icon-shared',
      actionHandler: function (filename,context) {
        fetch('http://127.0.0.1/nextcloud/index.php/apps/electronicsignatures/get_sign_link?path=' + filename)
          .then(response => response.json())
          .then(data => console.log(data));
        console.log(filename);
        console.log(context);
      }
    });
    console.log(fileList);
  }
};
OC.Plugins.register('OCA.Files.FileList', fileActionsPlugin);
