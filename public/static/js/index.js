/**
 * Created by hacfin on 2017/3/31.
 */
/**
 项目JS主入口
 以依赖Layui的layer和form模块为例
 **/
layui.define(['layer', 'form'], function (exports) {
    console.log(exports);
    var layer = layui.layer, form = layui.form();
    exports('index', {}); //注意，这里是模块输出的核心，模块名必须和use时的模块名一致
});

