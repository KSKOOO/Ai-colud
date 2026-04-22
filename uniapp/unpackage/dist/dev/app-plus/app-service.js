if (typeof Promise !== "undefined" && !Promise.prototype.finally) {
  Promise.prototype.finally = function(callback) {
    const promise = this.constructor;
    return this.then(
      (value) => promise.resolve(callback()).then(() => value),
      (reason) => promise.resolve(callback()).then(() => {
        throw reason;
      })
    );
  };
}
;
if (typeof uni !== "undefined" && uni && uni.requireGlobal) {
  const global = uni.requireGlobal();
  ArrayBuffer = global.ArrayBuffer;
  Int8Array = global.Int8Array;
  Uint8Array = global.Uint8Array;
  Uint8ClampedArray = global.Uint8ClampedArray;
  Int16Array = global.Int16Array;
  Uint16Array = global.Uint16Array;
  Int32Array = global.Int32Array;
  Uint32Array = global.Uint32Array;
  Float32Array = global.Float32Array;
  Float64Array = global.Float64Array;
  BigInt64Array = global.BigInt64Array;
  BigUint64Array = global.BigUint64Array;
}
;
if (uni.restoreGlobal) {
  uni.restoreGlobal(Vue, weex, plus, setTimeout, clearTimeout, setInterval, clearInterval);
}
(function(vue) {
  "use strict";
  function formatAppLog(type, filename, ...args) {
    if (uni.__log__) {
      uni.__log__(type, filename, ...args);
    } else {
      console[type].apply(console, [...args, filename]);
    }
  }
  const SERVER_CONFIG = {
    // 内网穿透/外网域名地址（生产环境）
    // 默认使用花生壳域名，可替换为实际域名或IP
    host: "demogod.online",
    port: "80",
    protocol: "http"
  };
  const getBaseURL = () => {
    return `${SERVER_CONFIG.protocol}://${SERVER_CONFIG.host}`;
  };
  const config = {
    baseURL: getBaseURL(),
    timeout: 3e4,
    // API 端点
    api: {
      // 登录和注册通过表单提交，不走 JSON API
      login: "/index.php?route=login",
      register: "/index.php?route=register",
      logout: "/index.php?route=logout",
      // JSON API 端点
      chat: "/api/api_handler.php",
      models: "/api/api_handler.php",
      providers: "/api/providers_handler.php",
      agents: "/api/agent_api.php",
      user: "/api/user_handler.php",
      scenarios: "/api/scenario_handler.php"
    },
    // 服务器配置
    server: SERVER_CONFIG,
    // 环境标识
    isDev: true
  };
  class HttpRequest {
    constructor() {
      this.baseURL = config.baseURL || "";
      this.timeout = config.timeout;
    }
    /**
     * 构建完整URL
     */
    buildURL(url) {
      if (url.startsWith("http")) {
        return url;
      }
      return this.baseURL + url;
    }
    /**
     * 发送请求
     */
    request(options) {
      return new Promise((resolve, reject) => {
        const token = uni.getStorageSync("token");
        const url = this.buildURL(options.url);
        formatAppLog("log", "at utils/request.js:43", "请求URL:", url);
        if (options.loading !== false) {
          uni.showLoading({
            title: options.loadingText || "加载中...",
            mask: true
          });
        }
        uni.request({
          url,
          method: options.method || "GET",
          data: options.data || {},
          withCredentials: options.withCredentials !== false,
          header: {
            "Content-Type": "application/x-www-form-urlencoded",
            "Authorization": token ? `Bearer ${token}` : "",
            ...options.header
          },
          timeout: options.timeout || this.timeout,
          success: (res) => {
            var _a, _b;
            if (options.loading !== false) {
              uni.hideLoading();
            }
            if (res.statusCode === 200) {
              if (res.data && (res.data.status === "success" || res.data.success)) {
                resolve(res.data);
              } else if (typeof res.data === "object") {
                resolve(res.data);
              } else {
                const msg = ((_a = res.data) == null ? void 0 : _a.message) || ((_b = res.data) == null ? void 0 : _b.error) || "请求失败";
                uni.showToast({
                  title: msg,
                  icon: "none",
                  duration: 2e3
                });
                reject(res.data);
              }
            } else if (res.statusCode === 401) {
              uni.removeStorageSync("token");
              uni.removeStorageSync("userInfo");
              uni.redirectTo({
                url: "/pages/login/login"
              });
              reject(res);
            } else if (res.statusCode === 404) {
              formatAppLog("error", "at utils/request.js:97", "404错误:", url);
              uni.showToast({
                title: "接口不存在(404)",
                icon: "none",
                duration: 2e3
              });
              reject({ statusCode: 404, message: "接口不存在" });
            } else {
              uni.showToast({
                title: `请求错误: ${res.statusCode}`,
                icon: "none",
                duration: 2e3
              });
              reject(res);
            }
          },
          fail: (err) => {
            if (options.loading !== false) {
              uni.hideLoading();
            }
            formatAppLog("error", "at utils/request.js:119", "请求失败:", err, "URL:", url);
            uni.showToast({
              title: "网络请求失败，请检查网络连接",
              icon: "none",
              duration: 2e3
            });
            reject(err);
          }
        });
      });
    }
    /**
     * GET 请求
     */
    get(url, data = {}, options = {}) {
      return this.request({
        url,
        method: "GET",
        data,
        ...options
      });
    }
    /**
     * POST 请求
     */
    post(url, data = {}, options = {}) {
      return this.request({
        url,
        method: "POST",
        data,
        ...options
      });
    }
    /**
     * 上传文件
     */
    upload(url, filePath, formData = {}) {
      return new Promise((resolve, reject) => {
        const token = uni.getStorageSync("token");
        const fullUrl = url.startsWith("http") ? url : this.baseURL + url;
        uni.uploadFile({
          url: fullUrl,
          filePath,
          name: "file",
          formData,
          header: {
            "Authorization": token ? `Bearer ${token}` : ""
          },
          success: (res) => {
            const data = JSON.parse(res.data);
            if (data.status === "success" || data.success) {
              resolve(data);
            } else {
              uni.showToast({
                title: data.message || data.error || "上传失败",
                icon: "none"
              });
              reject(data);
            }
          },
          fail: (err) => {
            uni.showToast({
              title: "上传失败",
              icon: "none"
            });
            reject(err);
          }
        });
      });
    }
  }
  const http = new HttpRequest();
  function saveUserSession(user) {
    uni.setStorageSync("token", "session_active");
    uni.setStorageSync("userInfo", user || {});
    if (!uni.getStorageSync("registerTime")) {
      uni.setStorageSync("registerTime", (/* @__PURE__ */ new Date()).toISOString());
    }
  }
  function clearUserSession() {
    uni.removeStorageSync("token");
    uni.removeStorageSync("userInfo");
  }
  function normalizeUserPayload(payload) {
    const user = (payload == null ? void 0 : payload.user) || (payload == null ? void 0 : payload.data) || payload || {};
    return {
      id: user.id || "",
      username: user.username || "",
      email: user.email || "",
      role: user.role || "user",
      created_at: user.created_at || ""
    };
  }
  function formRequest(url, data = {}, method = "POST") {
    return new Promise((resolve, reject) => {
      uni.request({
        url,
        method,
        data,
        header: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        withCredentials: true,
        success: (res) => resolve(res),
        fail: reject
      });
    });
  }
  async function fetchCurrentUserFromServer() {
    const res = await http.get(config.api.user, {
      action: "getCurrentUser"
    }, {
      loading: false,
      withCredentials: true
    });
    if (res.status !== "success" || !res.user) {
      throw new Error(res.message || "未获取到用户信息");
    }
    const user = normalizeUserPayload(res);
    saveUserSession(user);
    return user;
  }
  const userApi = {
    async login(data) {
      const url = config.baseURL + config.api.login;
      uni.showLoading({ title: "登录中...", mask: true });
      try {
        const res = await formRequest(url, {
          username: data.username,
          password: data.password
        });
        const responseText = typeof res.data === "string" ? res.data : JSON.stringify(res.data);
        if (res.statusCode >= 400 || responseText.includes("用户名或密码错误") || responseText.includes("请填写用户名和密码") || responseText.includes("账号已被禁用")) {
          throw new Error("用户名或密码错误");
        }
        const user = await fetchCurrentUserFromServer();
        uni.showToast({ title: "登录成功", icon: "success" });
        return {
          status: "success",
          message: "登录成功",
          token: "session_active",
          user
        };
      } finally {
        uni.hideLoading();
      }
    },
    async register(data) {
      const url = config.baseURL + config.api.register;
      uni.showLoading({ title: "注册中...", mask: true });
      try {
        const res = await formRequest(url, {
          username: data.username,
          password: data.password,
          email: data.email
        });
        const responseText = typeof res.data === "string" ? res.data : JSON.stringify(res.data);
        if (responseText.includes("用户名已存在")) {
          throw new Error("用户名已存在");
        }
        if (responseText.includes("邮箱已被注册")) {
          throw new Error("邮箱已被注册");
        }
        if (responseText.includes("注册失败")) {
          throw new Error("注册失败");
        }
        uni.showToast({ title: "注册成功", icon: "success" });
        return {
          status: "success",
          message: "注册成功"
        };
      } finally {
        uni.hideLoading();
      }
    },
    logout() {
      return new Promise((resolve) => {
        const url = config.baseURL + config.api.logout;
        uni.request({
          url,
          method: "GET",
          withCredentials: true,
          complete: () => {
            clearUserSession();
            resolve();
          }
        });
      });
    },
    checkLogin() {
      const token = uni.getStorageSync("token");
      const userInfo = uni.getStorageSync("userInfo");
      return !!(token && userInfo && userInfo.username);
    },
    async syncCurrentUser() {
      try {
        return await fetchCurrentUserFromServer();
      } catch (error) {
        clearUserSession();
        throw error;
      }
    },
    getCurrentUser() {
      return http.get(config.api.user, {
        action: "getCurrentUser"
      }, {
        loading: false,
        withCredentials: true
      });
    },
    getUserStats() {
      return http.get(config.api.user, {
        action: "get_usage_stats"
      }, {
        withCredentials: true
      });
    },
    getUserInfo() {
      return http.get(config.api.user, {
        action: "get_profile"
      }, {
        loading: false,
        withCredentials: true
      });
    },
    updateProfile(data) {
      return http.post(config.api.user, {
        action: "updateProfile",
        username: data.username || "",
        email: data.email || ""
      }, {
        withCredentials: true
      });
    },
    changePassword(data) {
      return http.post(config.api.user, {
        action: "changePassword",
        old_password: data.old_password || "",
        new_password: data.new_password || ""
      }, {
        withCredentials: true
      });
    }
  };
  const _export_sfc = (sfc, props) => {
    const target = sfc.__vccOpts || sfc;
    for (const [key, val] of props) {
      target[key] = val;
    }
    return target;
  };
  const _sfc_main$i = {
    data() {
      return {
        isLogin: true,
        loading: false,
        logoError: false,
        loginForm: {
          username: "",
          password: ""
        },
        registerForm: {
          username: "",
          email: "",
          password: "",
          confirmPassword: ""
        }
      };
    },
    methods: {
      // LOGO加载失败
      onLogoError() {
        this.logoError = true;
      },
      // 登录
      async handleLogin() {
        if (!this.loginForm.username) {
          uni.showToast({ title: "请输入用户名", icon: "none" });
          return;
        }
        if (!this.loginForm.password) {
          uni.showToast({ title: "请输入密码", icon: "none" });
          return;
        }
        this.loading = true;
        try {
          await userApi.login(this.loginForm);
          setTimeout(() => {
            uni.switchTab({
              url: "/pages/index/index"
            });
          }, 1e3);
        } catch (error) {
          formatAppLog("error", "at pages/login/login.vue:176", "登录失败:", error);
        } finally {
          this.loading = false;
        }
      },
      // 注册
      async handleRegister() {
        if (!this.registerForm.username) {
          uni.showToast({ title: "请输入用户名", icon: "none" });
          return;
        }
        if (!this.registerForm.email) {
          uni.showToast({ title: "请输入邮箱", icon: "none" });
          return;
        }
        if (!this.registerForm.password) {
          uni.showToast({ title: "请输入密码", icon: "none" });
          return;
        }
        if (this.registerForm.password !== this.registerForm.confirmPassword) {
          uni.showToast({ title: "两次密码不一致", icon: "none" });
          return;
        }
        this.loading = true;
        try {
          await userApi.register(this.registerForm);
          uni.showToast({ title: "注册成功，请登录", icon: "success" });
          this.isLogin = true;
          this.loginForm.username = this.registerForm.username;
        } catch (error) {
          formatAppLog("error", "at pages/login/login.vue:208", "注册失败:", error);
        } finally {
          this.loading = false;
        }
      }
    }
  };
  function _sfc_render$h(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "login-container" }, [
      vue.createElementVNode("view", { class: "bg-decoration" }, [
        vue.createElementVNode("view", { class: "circle circle-1" }),
        vue.createElementVNode("view", { class: "circle circle-2" }),
        vue.createElementVNode("view", { class: "circle circle-3" })
      ]),
      vue.createElementVNode("view", { class: "logo-area" }, [
        vue.createElementVNode("view", { class: "logo-icon" }, [
          vue.createElementVNode("text", { class: "logo-text" }, "凌")
        ]),
        vue.createElementVNode("text", { class: "app-name" }, "凌岳AI助手"),
        vue.createElementVNode("text", { class: "app-slogan" }, "智能对话，无限可能")
      ]),
      vue.createElementVNode("view", { class: "form-area" }, [
        vue.createElementVNode("view", { class: "tab-bar" }, [
          vue.createElementVNode(
            "view",
            {
              class: vue.normalizeClass(["tab-item", { active: $data.isLogin }]),
              onClick: _cache[0] || (_cache[0] = ($event) => $data.isLogin = true)
            },
            " 登录 ",
            2
            /* CLASS */
          ),
          vue.createElementVNode(
            "view",
            {
              class: vue.normalizeClass(["tab-item", { active: !$data.isLogin }]),
              onClick: _cache[1] || (_cache[1] = ($event) => $data.isLogin = false)
            },
            " 注册 ",
            2
            /* CLASS */
          )
        ]),
        $data.isLogin ? (vue.openBlock(), vue.createElementBlock("view", {
          key: 0,
          class: "form-content"
        }, [
          vue.createElementVNode("view", { class: "input-group" }, [
            vue.createElementVNode("view", { class: "input-icon" }, [
              vue.createElementVNode("text", null, "👤")
            ]),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                type: "text",
                "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.loginForm.username = $event),
                placeholder: "请输入用户名",
                "placeholder-class": "placeholder"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [vue.vModelText, $data.loginForm.username]
            ])
          ]),
          vue.createElementVNode("view", { class: "input-group" }, [
            vue.createElementVNode("view", { class: "input-icon" }, [
              vue.createElementVNode("text", null, "🔒")
            ]),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                type: "password",
                "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.loginForm.password = $event),
                placeholder: "请输入密码",
                "placeholder-class": "placeholder"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [vue.vModelText, $data.loginForm.password]
            ])
          ]),
          vue.createElementVNode("button", {
            class: "btn-submit",
            onClick: _cache[4] || (_cache[4] = (...args) => $options.handleLogin && $options.handleLogin(...args)),
            loading: $data.loading
          }, " 登录 ", 8, ["loading"])
        ])) : (vue.openBlock(), vue.createElementBlock("view", {
          key: 1,
          class: "form-content"
        }, [
          vue.createElementVNode("view", { class: "input-group" }, [
            vue.createElementVNode("view", { class: "input-icon" }, [
              vue.createElementVNode("text", null, "👤")
            ]),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                type: "text",
                "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.registerForm.username = $event),
                placeholder: "请输入用户名",
                "placeholder-class": "placeholder"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [vue.vModelText, $data.registerForm.username]
            ])
          ]),
          vue.createElementVNode("view", { class: "input-group" }, [
            vue.createElementVNode("view", { class: "input-icon" }, [
              vue.createElementVNode("text", null, "📧")
            ]),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                type: "text",
                "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.registerForm.email = $event),
                placeholder: "请输入邮箱",
                "placeholder-class": "placeholder"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [vue.vModelText, $data.registerForm.email]
            ])
          ]),
          vue.createElementVNode("view", { class: "input-group" }, [
            vue.createElementVNode("view", { class: "input-icon" }, [
              vue.createElementVNode("text", null, "🔒")
            ]),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                type: "password",
                "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.registerForm.password = $event),
                placeholder: "请输入密码",
                "placeholder-class": "placeholder"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [vue.vModelText, $data.registerForm.password]
            ])
          ]),
          vue.createElementVNode("view", { class: "input-group" }, [
            vue.createElementVNode("view", { class: "input-icon" }, [
              vue.createElementVNode("text", null, "🔒")
            ]),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                type: "password",
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.registerForm.confirmPassword = $event),
                placeholder: "请确认密码",
                "placeholder-class": "placeholder"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [vue.vModelText, $data.registerForm.confirmPassword]
            ])
          ]),
          vue.createElementVNode("button", {
            class: "btn-submit",
            onClick: _cache[9] || (_cache[9] = (...args) => $options.handleRegister && $options.handleRegister(...args)),
            loading: $data.loading
          }, " 注册 ", 8, ["loading"])
        ]))
      ]),
      vue.createElementVNode("view", { class: "footer" }, [
        vue.createElementVNode("text", { class: "copyright" }, "© 2024 凌岳科技")
      ])
    ]);
  }
  const PagesLoginLogin = /* @__PURE__ */ _export_sfc(_sfc_main$i, [["render", _sfc_render$h], ["__scopeId", "data-v-e4e4508d"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/login/login.vue"]]);
  const chatApi = {
    /**
     * 发送聊天消息
     */
    sendMessage(data) {
      return http.post(config.api.chat, {
        request: "chat",
        input: data.input,
        model: data.model || "",
        mode: data.mode || "normal",
        provider_id: data.provider_id || "",
        context: data.context || "[]"
      }, {
        loadingText: "思考中..."
      });
    },
    /**
     * 获取场景演示列表
     */
    getScenarios() {
      return http.get(config.api.scenarios, {
        action: "list"
      }, {
        loading: false
      });
    },
    /**
     * 深度思考模式
     */
    deepThink(data) {
      return http.post(config.api.chat, {
        request: "deep_think",
        input: data.input,
        model: data.model || "",
        mode: "deep_think",
        provider_id: data.provider_id || "",
        context: data.context || "[]"
      }, {
        loadingText: "深度思考中..."
      });
    },
    /**
     * 联网搜索模式
     */
    webSearch(data) {
      return http.post(config.api.chat, {
        request: "web_search",
        input: data.input,
        model: data.model || "",
        mode: "web_search",
        provider_id: data.provider_id || "",
        context: data.context || "[]"
      }, {
        loadingText: "搜索中..."
      });
    },
    /**
     * 获取可用模型列表
     */
    getModels() {
      return http.get(config.api.models, {}, {
        loading: false
      });
    },
    /**
     * 获取提供商列表
     */
    getProviders(enabled = 1) {
      return http.get(config.api.providers, {
        action: "get_providers",
        enabled
      }, {
        loading: false
      });
    },
    /**
     * 获取提供商模型
     */
    getProviderModels(providerId) {
      return http.get(config.api.providers, {
        action: "fetch_models",
        provider_id: providerId
      }, {
        loading: false
      });
    }
  };
  const AGENT_MANAGE_API = "/api/agent_handler.php";
  const agentsApi = {
    /**
     * 获取智能体列表
     */
    getAgents(params = {}) {
      return http.get(AGENT_MANAGE_API, {
        action: "getAgents",
        page: params.page || 1,
        limit: params.limit || 10,
        category: params.category || "",
        keyword: params.keyword || ""
      });
    },
    /**
     * 获取我的智能体列表
     */
    getMyAgents(params = {}) {
      return http.get(AGENT_MANAGE_API, {
        action: "getMyAgents",
        page: params.page || 1,
        limit: params.limit || 10
      });
    },
    /**
     * 获取智能体详情
     */
    getAgentDetail(agentId) {
      return http.get(AGENT_MANAGE_API, {
        action: "getAgent",
        agent_id: agentId
      });
    },
    /**
     * 创建智能体
     */
    createAgent(data) {
      return http.post(AGENT_MANAGE_API, {
        action: "createAgent",
        data: JSON.stringify(data)
      });
    },
    /**
     * 更新智能体
     */
    updateAgent(agentId, data) {
      return http.post(AGENT_MANAGE_API, {
        action: "updateAgent",
        agent_id: agentId,
        data: JSON.stringify(data)
      });
    },
    /**
     * 删除智能体
     */
    deleteAgent(agentId) {
      return http.post(AGENT_MANAGE_API, {
        action: "deleteAgent",
        agent_id: agentId
      });
    },
    /**
     * 部署智能体 - 生成外部访问token
     */
    deployAgent(agentId) {
      return http.post(AGENT_MANAGE_API, {
        action: "deployAgent",
        agent_id: agentId
      });
    },
    /**
     * 取消部署智能体
     */
    undeployAgent(agentId) {
      return http.post(AGENT_MANAGE_API, {
        action: "undeployAgent",
        agent_id: agentId
      });
    },
    /**
     * 与智能体对话 - 使用agent_chat端点
     */
    chatWithAgent(data) {
      return http.post("/index.php?route=agent_chat", {
        token: data.token || "",
        message: data.message,
        session_id: data.session_id || ""
      });
    },
    /**
     * 获取智能体聊天历史
     */
    getChatHistory(agentId, sessionId = "") {
      return http.get(AGENT_MANAGE_API, {
        action: "getChatHistory",
        agent_id: agentId,
        session_id: sessionId
      });
    },
    /**
     * 获取可用模型列表（本地+在线）
     */
    getAvailableModels() {
      return http.get(config.api.models, {
        request: "models"
      });
    },
    /**
     * 获取在线API提供商列表
     */
    getProviders() {
      return http.get(config.api.providers, {
        action: "get_providers",
        enabled: 1
      });
    }
  };
  const _sfc_main$h = {
    data() {
      return {
        userInfo: {},
        demoList: [],
        agentList: [
          { id: 1, icon: "🧠", name: "深度思考助手", description: "帮你深入分析复杂问题" },
          { id: 2, icon: "📖", name: "阅读理解专家", description: "快速理解文章核心内容" },
          { id: 3, icon: "🎵", name: "音乐创作助手", description: "歌词创作与音乐建议" }
        ],
        recentChats: [],
        scenariosLoaded: false
      };
    },
    onLoad() {
      this.loadUserInfo();
      this.loadAgents();
      this.loadRecentChats();
      this.loadScenarios();
    },
    onShow() {
      this.loadRecentChats();
    },
    onPullDownRefresh() {
      this.loadAgents();
      this.loadRecentChats();
      setTimeout(() => {
        uni.stopPullDownRefresh();
      }, 500);
    },
    methods: {
      // 加载用户信息
      loadUserInfo() {
        this.userInfo = uni.getStorageSync("userInfo") || {};
      },
      // 加载智能体列表
      async loadAgents() {
        try {
          const res = await agentsApi.getAgents({ limit: 3 });
          if (res.data && res.data.length > 0) {
            this.agentList = res.data;
          }
        } catch (error) {
          formatAppLog("log", "at pages/index/index.vue:166", "加载智能体列表失败，使用默认数据");
        }
      },
      // 加载最近对话
      loadRecentChats() {
        this.recentChats = uni.getStorageSync("recentChats") || [];
      },
      // 加载场景演示（从后端API获取）
      async loadScenarios() {
        try {
          const res = await chatApi.getScenarios();
          if (res.data && res.data.length > 0) {
            this.demoList = res.data.map((item) => ({
              icon: item.icon || "🎯",
              title: item.name,
              desc: item.description,
              prompt: item.prompt || item.default_prompt
            }));
            this.scenariosLoaded = true;
          } else {
            this.loadDefaultScenarios();
          }
        } catch (error) {
          formatAppLog("log", "at pages/index/index.vue:191", "加载场景演示失败，使用默认数据");
          this.loadDefaultScenarios();
        }
      },
      // 加载默认场景
      loadDefaultScenarios() {
        this.demoList = [
          { icon: "📝", title: "智能写作", desc: "帮你写文章、文案", prompt: "请帮我写一篇关于人工智能发展的文章" },
          { icon: "💻", title: "代码助手", desc: "编程问题解答", prompt: "请解释一下什么是递归，并给出一个Python示例" },
          { icon: "📊", title: "数据分析", desc: "数据解读分析", prompt: "请帮我分析这组销售数据的趋势" },
          { icon: "🎨", title: "创意设计", desc: "设计灵感建议", prompt: "请给我一些现代简约风格UI设计的建议" },
          { icon: "📚", title: "学习辅导", desc: "知识答疑解惑", prompt: "请解释量子计算的基本原理" },
          { icon: "🌍", title: "翻译助手", desc: "多语言翻译", prompt: "请将这段话翻译成英文：人工智能正在改变我们的生活" }
        ];
      },
      // 页面跳转
      navigateTo(url) {
        if (url.startsWith("/pages/chat/chat")) {
          const [path, query] = url.split("?");
          uni.switchTab({
            url: path,
            success: () => {
              var _a;
              if (query) {
                const mode = ((_a = query.split("&").find((item) => item.startsWith("mode="))) == null ? void 0 : _a.split("=")[1]) || "";
                if (mode) {
                  uni.$emit("setChatMode", mode);
                }
              }
            }
          });
        } else if (url.startsWith("/pages/index/index") || url.startsWith("/pages/agents/agents") || url.startsWith("/pages/mine/mine")) {
          uni.switchTab({ url });
        } else {
          uni.navigateTo({ url });
        }
      },
      // 开始演示
      startDemo(demo) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            uni.$emit("setChatInput", demo.prompt);
          }
        });
      },
      // 显示所有场景
      showAllScenarios() {
        uni.navigateTo({
          url: "/pages/scenarios/scenarios",
          fail: () => {
            uni.showToast({ title: "场景列表页开发中", icon: "none" });
          }
        });
      },
      // 清空历史
      clearHistory() {
        uni.showModal({
          title: "确认清空",
          content: "确定要清空所有历史记录吗？",
          success: (res) => {
            if (res.confirm) {
              uni.removeStorageSync("recentChats");
              this.recentChats = [];
              uni.showToast({ title: "已清空", icon: "success" });
            }
          }
        });
      },
      // 继续对话
      continueChat(chat) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            uni.$emit("loadChatHistory", chat);
          }
        });
      }
    }
  };
  function _sfc_render$g(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "index-container" }, [
      vue.createElementVNode("view", { class: "welcome-section" }, [
        vue.createElementVNode("view", { class: "welcome-content" }, [
          vue.createElementVNode("view", { class: "header-logo" }, [
            vue.createElementVNode("text", { class: "logo-text" }, "凌")
          ]),
          vue.createElementVNode("text", { class: "welcome-title" }, "欢迎回来"),
          vue.createElementVNode(
            "text",
            { class: "welcome-name" },
            vue.toDisplayString($data.userInfo.username || "用户"),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", { class: "welcome-avatar" }, [
          vue.createElementVNode(
            "text",
            { class: "avatar-text" },
            vue.toDisplayString(($data.userInfo.username || "U")[0].toUpperCase()),
            1
            /* TEXT */
          )
        ])
      ]),
      vue.createElementVNode("view", { class: "quick-actions" }, [
        vue.createElementVNode("view", {
          class: "action-item",
          onClick: _cache[0] || (_cache[0] = ($event) => $options.navigateTo("/pages/chat/chat"))
        }, [
          vue.createElementVNode("view", {
            class: "action-icon",
            style: { "background": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)" }
          }, [
            vue.createElementVNode("text", null, "💬")
          ]),
          vue.createElementVNode("text", { class: "action-name" }, "AI聊天")
        ]),
        vue.createElementVNode("view", {
          class: "action-item",
          onClick: _cache[1] || (_cache[1] = ($event) => $options.navigateTo("/pages/agents/agents"))
        }, [
          vue.createElementVNode("view", {
            class: "action-icon",
            style: { "background": "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }
          }, [
            vue.createElementVNode("text", null, "🤖")
          ]),
          vue.createElementVNode("text", { class: "action-name" }, "智能体")
        ]),
        vue.createElementVNode("view", {
          class: "action-item",
          onClick: _cache[2] || (_cache[2] = ($event) => $options.navigateTo("/pages/chat/chat?mode=vision"))
        }, [
          vue.createElementVNode("view", {
            class: "action-icon",
            style: { "background": "linear-gradient(135deg, #f59e0b 0%, #d97706 100%)" }
          }, [
            vue.createElementVNode("text", null, "🖼️")
          ]),
          vue.createElementVNode("text", { class: "action-name" }, "图像分析")
        ]),
        vue.createElementVNode("view", {
          class: "action-item",
          onClick: _cache[3] || (_cache[3] = ($event) => $options.navigateTo("/pages/chat/chat?mode=video"))
        }, [
          vue.createElementVNode("view", {
            class: "action-icon",
            style: { "background": "linear-gradient(135deg, #ef4444 0%, #dc2626 100%)" }
          }, [
            vue.createElementVNode("text", null, "🎬")
          ]),
          vue.createElementVNode("text", { class: "action-name" }, "视频生成")
        ])
      ]),
      vue.createElementVNode("view", { class: "section" }, [
        vue.createElementVNode("view", { class: "section-header" }, [
          vue.createElementVNode("text", { class: "section-title" }, "功能演示"),
          vue.createElementVNode("text", {
            class: "section-more",
            onClick: _cache[4] || (_cache[4] = (...args) => $options.showAllScenarios && $options.showAllScenarios(...args))
          }, "查看全部")
        ]),
        vue.createElementVNode("scroll-view", {
          "scroll-x": "",
          class: "demo-cards"
        }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.demoList, (demo, index) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "demo-card",
                key: index,
                onClick: ($event) => $options.startDemo(demo)
              }, [
                vue.createElementVNode(
                  "view",
                  { class: "demo-icon" },
                  vue.toDisplayString(demo.icon),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode(
                  "text",
                  { class: "demo-title" },
                  vue.toDisplayString(demo.title),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode(
                  "text",
                  { class: "demo-desc" },
                  vue.toDisplayString(demo.desc),
                  1
                  /* TEXT */
                )
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ])
      ]),
      vue.createElementVNode("view", { class: "section" }, [
        vue.createElementVNode("view", { class: "section-header" }, [
          vue.createElementVNode("text", { class: "section-title" }, "热门智能体")
        ]),
        vue.createElementVNode("view", { class: "agent-list" }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.agentList, (agent) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "agent-item",
                key: agent.id,
                onClick: ($event) => $options.navigateTo("/pages/agent-detail/agent-detail?id=" + agent.id)
              }, [
                vue.createElementVNode(
                  "view",
                  { class: "agent-avatar" },
                  vue.toDisplayString(agent.icon),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode("view", { class: "agent-info" }, [
                  vue.createElementVNode(
                    "text",
                    { class: "agent-name" },
                    vue.toDisplayString(agent.name),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode(
                    "text",
                    { class: "agent-desc" },
                    vue.toDisplayString(agent.description),
                    1
                    /* TEXT */
                  )
                ]),
                vue.createElementVNode("text", { class: "agent-arrow" }, "→")
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ])
      ]),
      $data.recentChats.length > 0 ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 0,
        class: "section"
      }, [
        vue.createElementVNode("view", { class: "section-header" }, [
          vue.createElementVNode("text", { class: "section-title" }, "最近对话"),
          vue.createElementVNode("text", {
            class: "section-more",
            onClick: _cache[5] || (_cache[5] = (...args) => $options.clearHistory && $options.clearHistory(...args))
          }, "清空")
        ]),
        vue.createElementVNode("view", { class: "chat-history" }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.recentChats, (chat, index) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "history-item",
                key: index,
                onClick: ($event) => $options.continueChat(chat)
              }, [
                vue.createElementVNode("view", { class: "history-icon" }, "💬"),
                vue.createElementVNode("view", { class: "history-content" }, [
                  vue.createElementVNode(
                    "text",
                    { class: "history-title" },
                    vue.toDisplayString(chat.title),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode(
                    "text",
                    { class: "history-time" },
                    vue.toDisplayString(chat.time),
                    1
                    /* TEXT */
                  )
                ])
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ])
      ])) : vue.createCommentVNode("v-if", true)
    ]);
  }
  const PagesIndexIndex = /* @__PURE__ */ _export_sfc(_sfc_main$h, [["render", _sfc_render$g], ["__scopeId", "data-v-1cf27b2a"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/index/index.vue"]]);
  function parseMarkdown(text) {
    if (!text)
      return "";
    text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code class="language-$1">$2</code></pre>');
    text = text.replace(/`([^`]+)`/g, "<code>$1</code>");
    text = text.replace(/^### (.+)$/gm, "<h3>$1</h3>");
    text = text.replace(/^## (.+)$/gm, "<h2>$1</h2>");
    text = text.replace(/^# (.+)$/gm, "<h1>$1</h1>");
    text = text.replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>");
    text = text.replace(/\*(.+?)\*/g, "<em>$1</em>");
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
    text = text.replace(/\n/g, "<br>");
    return text;
  }
  const _sfc_main$g = {
    data() {
      return {
        messages: [],
        inputText: "",
        isLoading: false,
        scrollTop: 0,
        currentMode: "normal",
        currentModel: "",
        currentProvider: "",
        showModelPopup: false,
        modelList: [],
        contextMessages: []
      };
    },
    computed: {
      canSend() {
        return !!this.inputText.trim() && !this.isLoading;
      }
    },
    onLoad(options) {
      if (options.mode) {
        this.currentMode = options.mode;
      }
      this.loadModels();
      uni.$on("setChatInput", this.handleSetChatInput);
      uni.$on("loadChatHistory", this.handleLoadChatHistory);
      uni.$on("selectModel", this.handleSelectModel);
      uni.$on("setChatMode", this.handleSetChatMode);
    },
    onShow() {
      const currentAgent = getApp().globalData.currentAgent;
      if (currentAgent && currentAgent.model_id && currentAgent.model_provider) {
        this.currentModel = currentAgent.model_id;
        this.currentProvider = currentAgent.model_provider;
      }
    },
    onUnload() {
      uni.$off("setChatInput", this.handleSetChatInput);
      uni.$off("loadChatHistory", this.handleLoadChatHistory);
      uni.$off("selectModel", this.handleSelectModel);
      uni.$off("setChatMode", this.handleSetChatMode);
    },
    methods: {
      parseMarkdown,
      async loadModels() {
        try {
          const res = await chatApi.getProviders();
          const providers = res.data || [];
          const models = [];
          providers.forEach((provider) => {
            const providerModels = Array.isArray(provider.models) ? provider.models : [];
            providerModels.forEach((modelName) => {
              models.push({
                name: modelName,
                provider: provider.id,
                providerName: provider.name || provider.id,
                remark: this.getModelRemark(modelName, provider.name || provider.id)
              });
            });
          });
          this.modelList = models;
          const defaultProvider = providers.find((item) => item.is_default) || providers[0];
          if (defaultProvider) {
            this.currentProvider = defaultProvider.id;
            this.currentModel = defaultProvider.default_model || defaultProvider.models && defaultProvider.models[0] || "";
          }
        } catch (error) {
          formatAppLog("error", "at pages/chat/chat.vue:171", "loadModels failed", error);
          this.modelList = [];
          this.currentProvider = "";
          this.currentModel = "";
        }
      },
      getModelRemark(name, providerName) {
        const lower = String(name || "").toLowerCase();
        let type = "通用模型";
        if (lower.includes("vl") || lower.includes("vision"))
          type = "图文理解";
        else if (lower.includes("ocr"))
          type = "OCR";
        else if (lower.includes("r1"))
          type = "推理模型";
        else if (lower.includes("code"))
          type = "代码生成";
        else if (lower.includes("chat"))
          type = "对话";
        else if (lower.includes("instruct"))
          type = "指令模型";
        else if (lower.includes("image"))
          type = "图像";
        else if (lower.includes("video"))
          type = "视频";
        return `${type} · ${providerName || "AI"}`;
      },
      toggleMode(mode) {
        this.currentMode = this.currentMode === mode ? "normal" : mode;
      },
      getModeLabel(mode) {
        const map = {
          deep_think: "深度思考",
          web_search: "联网搜索",
          vision_analysis: "图像分析"
        };
        return map[mode] || "";
      },
      selectModel(model) {
        if (!model)
          return;
        this.currentModel = model.name;
        this.currentProvider = model.provider;
        this.showModelPopup = false;
        uni.showToast({
          title: `已切换到 ${model.providerName}`,
          icon: "none"
        });
      },
      handleSetChatInput(text) {
        this.inputText = text || "";
      },
      handleLoadChatHistory(chat) {
        this.messages = Array.isArray(chat.messages) ? chat.messages : [];
        this.contextMessages = this.messages.map((item) => ({
          role: item.role,
          content: item.content
        }));
      },
      handleSelectModel(model) {
        if (model && typeof model === "object") {
          this.selectModel({
            name: model.name,
            provider: model.provider || model.provider_id,
            providerName: model.providerName || model.provider_name || model.provider || model.provider_id,
            remark: model.remark || this.getModelRemark(model.name, model.providerName || model.provider_name)
          });
        } else if (typeof model === "string") {
          const target = this.modelList.find((item) => item.name === model);
          if (target)
            this.selectModel(target);
        }
      },
      handleSetChatMode(mode) {
        this.currentMode = mode || "normal";
      },
      async sendMessage() {
        if (!this.canSend)
          return;
        if (!this.currentProvider || !this.currentModel) {
          uni.showToast({
            title: "请先选择模型",
            icon: "none"
          });
          return;
        }
        const content = this.inputText.trim();
        this.inputText = "";
        const userMessage = {
          role: "user",
          content,
          modeLabel: this.currentMode !== "normal" ? this.getModeLabel(this.currentMode) : ""
        };
        this.messages.push(userMessage);
        this.contextMessages.push({ role: "user", content });
        this.scrollToBottom();
        this.isLoading = true;
        try {
          let res;
          const payload = {
            input: content,
            model: this.currentModel,
            provider_id: this.currentProvider,
            context: JSON.stringify(this.contextMessages.slice(-10))
          };
          if (this.currentMode === "deep_think") {
            res = await chatApi.deepThink(payload);
          } else if (this.currentMode === "web_search") {
            res = await chatApi.webSearch(payload);
          } else {
            res = await chatApi.sendMessage({
              ...payload,
              mode: this.currentMode
            });
          }
          const assistantMessage = {
            role: "assistant",
            content: res.message || "未收到有效回复",
            modeLabel: ""
          };
          this.messages.push(assistantMessage);
          this.contextMessages.push({ role: "assistant", content: assistantMessage.content });
          this.saveHistory(content);
        } catch (error) {
          formatAppLog("error", "at pages/chat/chat.vue:301", "sendMessage failed", error);
          this.messages.push({
            role: "assistant",
            content: "请求失败，请稍后重试。",
            modeLabel: ""
          });
        } finally {
          this.isLoading = false;
          this.scrollToBottom();
        }
      },
      clearChat() {
        this.messages = [];
        this.contextMessages = [];
      },
      scrollToBottom() {
        this.$nextTick(() => {
          this.scrollTop = this.messages.length * 999;
        });
      },
      saveHistory(title) {
        const historyItem = {
          title: title.substring(0, 30),
          preview: title.substring(0, 60),
          time: (/* @__PURE__ */ new Date()).toLocaleString(),
          type: "chat",
          model: this.currentModel,
          messages: this.messages.slice(-20)
        };
        const recentChats = uni.getStorageSync("recentChats") || [];
        recentChats.unshift(historyItem);
        uni.setStorageSync("recentChats", recentChats.slice(0, 10));
        const chatHistory = uni.getStorageSync("chatHistory") || [];
        chatHistory.unshift(historyItem);
        uni.setStorageSync("chatHistory", chatHistory.slice(0, 50));
      }
    }
  };
  function _sfc_render$f(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "chat-container" }, [
      vue.createElementVNode("scroll-view", {
        class: "message-list",
        "scroll-y": "",
        "scroll-top": $data.scrollTop
      }, [
        $data.messages.length === 0 ? (vue.openBlock(), vue.createElementBlock("view", {
          key: 0,
          class: "message-item assistant"
        }, [
          vue.createElementVNode("view", { class: "message-avatar" }, "AI"),
          vue.createElementVNode("view", { class: "message-bubble assistant" }, [
            vue.createElementVNode("text", null, "你好，我可以帮助你进行聊天、深度思考和联网搜索。")
          ])
        ])) : vue.createCommentVNode("v-if", true),
        (vue.openBlock(true), vue.createElementBlock(
          vue.Fragment,
          null,
          vue.renderList($data.messages, (msg, index) => {
            return vue.openBlock(), vue.createElementBlock(
              "view",
              {
                key: index,
                class: vue.normalizeClass(["message-item", msg.role === "user" ? "user" : "assistant"])
              },
              [
                vue.createElementVNode(
                  "view",
                  { class: "message-avatar" },
                  vue.toDisplayString(msg.role === "user" ? "我" : "AI"),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode(
                  "view",
                  {
                    class: vue.normalizeClass(["message-bubble", msg.role === "user" ? "user" : "assistant"])
                  },
                  [
                    msg.role !== "user" ? (vue.openBlock(), vue.createElementBlock("rich-text", {
                      key: 0,
                      nodes: $options.parseMarkdown(msg.content || "")
                    }, null, 8, ["nodes"])) : (vue.openBlock(), vue.createElementBlock(
                      "text",
                      { key: 1 },
                      vue.toDisplayString(msg.content),
                      1
                      /* TEXT */
                    )),
                    msg.modeLabel ? (vue.openBlock(), vue.createElementBlock(
                      "view",
                      {
                        key: 2,
                        class: "message-tag"
                      },
                      vue.toDisplayString(msg.modeLabel),
                      1
                      /* TEXT */
                    )) : vue.createCommentVNode("v-if", true)
                  ],
                  2
                  /* CLASS */
                )
              ],
              2
              /* CLASS */
            );
          }),
          128
          /* KEYED_FRAGMENT */
        )),
        $data.isLoading ? (vue.openBlock(), vue.createElementBlock("view", {
          key: 1,
          class: "message-item assistant"
        }, [
          vue.createElementVNode("view", { class: "message-avatar" }, "AI"),
          vue.createElementVNode("view", { class: "message-bubble assistant" }, [
            vue.createElementVNode("text", null, "正在生成回复...")
          ])
        ])) : vue.createCommentVNode("v-if", true)
      ], 8, ["scroll-top"]),
      vue.createElementVNode("view", { class: "toolbar" }, [
        vue.createElementVNode(
          "view",
          {
            class: vue.normalizeClass(["mode-chip", { active: $data.currentMode === "deep_think" }]),
            onClick: _cache[0] || (_cache[0] = ($event) => $options.toggleMode("deep_think"))
          },
          [
            vue.createElementVNode("text", null, "深度思考")
          ],
          2
          /* CLASS */
        ),
        vue.createElementVNode(
          "view",
          {
            class: vue.normalizeClass(["mode-chip", { active: $data.currentMode === "web_search" }]),
            onClick: _cache[1] || (_cache[1] = ($event) => $options.toggleMode("web_search"))
          },
          [
            vue.createElementVNode("text", null, "联网搜索")
          ],
          2
          /* CLASS */
        ),
        vue.createElementVNode("view", {
          class: "model-chip",
          onClick: _cache[2] || (_cache[2] = ($event) => $data.showModelPopup = true)
        }, [
          vue.createElementVNode(
            "text",
            null,
            vue.toDisplayString($data.currentModel || "选择模型"),
            1
            /* TEXT */
          )
        ])
      ]),
      vue.createElementVNode("view", { class: "input-area" }, [
        vue.withDirectives(vue.createElementVNode(
          "textarea",
          {
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.inputText = $event),
            class: "chat-input",
            placeholder: "输入消息...",
            "auto-height": "",
            maxlength: 4e3,
            "show-confirm-bar": false
          },
          null,
          512
          /* NEED_PATCH */
        ), [
          [vue.vModelText, $data.inputText]
        ]),
        vue.createElementVNode("view", { class: "action-row" }, [
          vue.createElementVNode("view", {
            class: "clear-btn",
            onClick: _cache[4] || (_cache[4] = (...args) => $options.clearChat && $options.clearChat(...args))
          }, "清空"),
          vue.createElementVNode(
            "view",
            {
              class: vue.normalizeClass(["send-btn", { disabled: !$options.canSend }]),
              onClick: _cache[5] || (_cache[5] = (...args) => $options.sendMessage && $options.sendMessage(...args))
            },
            "发送",
            2
            /* CLASS */
          )
        ])
      ]),
      $data.showModelPopup ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 0,
        class: "popup-mask",
        onClick: _cache[8] || (_cache[8] = ($event) => $data.showModelPopup = false)
      }, [
        vue.createElementVNode("view", {
          class: "popup-panel",
          onClick: _cache[7] || (_cache[7] = vue.withModifiers(() => {
          }, ["stop"]))
        }, [
          vue.createElementVNode("view", { class: "popup-header" }, [
            vue.createElementVNode("text", { class: "popup-title" }, "选择模型"),
            vue.createElementVNode("text", {
              class: "popup-close",
              onClick: _cache[6] || (_cache[6] = ($event) => $data.showModelPopup = false)
            }, "关闭")
          ]),
          vue.createElementVNode("scroll-view", {
            class: "popup-list",
            "scroll-y": ""
          }, [
            (vue.openBlock(true), vue.createElementBlock(
              vue.Fragment,
              null,
              vue.renderList($data.modelList, (model) => {
                return vue.openBlock(), vue.createElementBlock("view", {
                  key: model.provider + ":" + model.name,
                  class: vue.normalizeClass(["popup-item", { selected: $data.currentProvider === model.provider && $data.currentModel === model.name }]),
                  onClick: ($event) => $options.selectModel(model)
                }, [
                  vue.createElementVNode("view", { class: "popup-item-main" }, [
                    vue.createElementVNode(
                      "text",
                      { class: "popup-item-name" },
                      vue.toDisplayString(model.name),
                      1
                      /* TEXT */
                    ),
                    vue.createElementVNode(
                      "text",
                      { class: "popup-item-remark" },
                      vue.toDisplayString(model.remark),
                      1
                      /* TEXT */
                    )
                  ]),
                  vue.createElementVNode("view", { class: "popup-item-side" }, [
                    vue.createElementVNode(
                      "text",
                      { class: "popup-provider" },
                      vue.toDisplayString(model.providerName),
                      1
                      /* TEXT */
                    )
                  ])
                ], 10, ["onClick"]);
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ])
        ])
      ])) : vue.createCommentVNode("v-if", true)
    ]);
  }
  const PagesChatChat = /* @__PURE__ */ _export_sfc(_sfc_main$g, [["render", _sfc_render$f], ["__scopeId", "data-v-0a633310"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/chat/chat.vue"]]);
  const _sfc_main$f = {
    data() {
      return {
        searchKeyword: "",
        currentCategory: "all",
        categories: [
          { id: "writing", name: "写作" },
          { id: "coding", name: "编程" },
          { id: "analysis", name: "分析" },
          { id: "creative", name: "创意" },
          { id: "education", name: "教育" },
          { id: "business", name: "商务" }
        ],
        agentList: [],
        loading: false,
        page: 1,
        hasMore: true
      };
    },
    onLoad() {
      this.loadAgents();
    },
    onShow() {
      this.loadAgents();
    },
    onPullDownRefresh() {
      this.page = 1;
      this.hasMore = true;
      this.loadAgents().then(() => {
        uni.stopPullDownRefresh();
      });
    },
    methods: {
      // 加载智能体列表
      async loadAgents() {
        if (this.loading)
          return;
        this.loading = true;
        try {
          const res = await agentsApi.getAgents({
            page: this.page,
            category: this.currentCategory === "all" ? "" : this.currentCategory,
            keyword: this.searchKeyword
          });
          if (res.success && res.agents) {
            const agents = res.agents.map((agent) => {
              let tags = [];
              if (agent.tags) {
                try {
                  tags = JSON.parse(agent.tags);
                } catch (e) {
                  tags = agent.tags.split(",").map((t) => t.trim()).filter((t) => t);
                }
              }
              return {
                ...agent,
                // 兼容字段映射
                icon: agent.icon || "🤖",
                category_name: agent.category || "通用",
                tags,
                chat_count: agent.total_tasks || agent.usage_count || 0,
                likes: Math.floor(Math.random() * 1e3)
                // 模拟点赞数
              };
            });
            if (this.page === 1) {
              this.agentList = agents;
            } else {
              this.agentList = [...this.agentList, ...agents];
            }
            this.hasMore = agents.length >= 10;
          } else {
            if (this.page === 1) {
              this.agentList = [];
            }
            this.hasMore = false;
          }
        } catch (error) {
          formatAppLog("error", "at pages/agents/agents.vue:184", "加载智能体失败:", error);
          uni.showToast({ title: "加载失败", icon: "none" });
          if (this.page === 1) {
            this.agentList = [];
          }
        } finally {
          this.loading = false;
        }
      },
      // 加载更多
      loadMore() {
        if (this.hasMore && !this.loading) {
          this.page++;
          this.loadAgents();
        }
      },
      // 搜索
      handleSearch() {
        this.page = 1;
        this.loadAgents();
      },
      // 切换分类
      changeCategory(categoryId) {
        this.currentCategory = categoryId;
        this.page = 1;
        this.loadAgents();
      },
      // 跳转详情
      navigateToDetail(agent) {
        uni.navigateTo({
          url: `/pages/agent-detail/agent-detail?id=${agent.id}`
        });
      },
      // 快速对话
      quickChat(agent) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            getApp().globalData.currentAgent = agent;
            uni.$emit("setChatInput", `你好，${agent.name}！`);
          }
        });
      },
      // 创建智能体
      createAgent() {
        uni.navigateTo({
          url: "/pages/agent-create/agent-create"
        });
      }
    }
  };
  function _sfc_render$e(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "agents-container" }, [
      vue.createElementVNode("view", { class: "search-bar" }, [
        vue.createElementVNode("view", { class: "search-input" }, [
          vue.createElementVNode("text", { class: "search-icon" }, "🔍"),
          vue.withDirectives(vue.createElementVNode(
            "input",
            {
              type: "text",
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.searchKeyword = $event),
              placeholder: "搜索智能体...",
              "placeholder-class": "placeholder",
              onInput: _cache[1] || (_cache[1] = (...args) => $options.handleSearch && $options.handleSearch(...args))
            },
            null,
            544
            /* NEED_HYDRATION, NEED_PATCH */
          ), [
            [vue.vModelText, $data.searchKeyword]
          ])
        ])
      ]),
      vue.createElementVNode("scroll-view", {
        "scroll-x": "",
        class: "category-tabs"
      }, [
        vue.createElementVNode(
          "view",
          {
            class: vue.normalizeClass(["tab-item", { active: $data.currentCategory === "all" }]),
            onClick: _cache[2] || (_cache[2] = ($event) => $options.changeCategory("all"))
          },
          " 全部 ",
          2
          /* CLASS */
        ),
        (vue.openBlock(true), vue.createElementBlock(
          vue.Fragment,
          null,
          vue.renderList($data.categories, (cat) => {
            return vue.openBlock(), vue.createElementBlock("view", {
              class: vue.normalizeClass(["tab-item", { active: $data.currentCategory === cat.id }]),
              key: cat.id,
              onClick: ($event) => $options.changeCategory(cat.id)
            }, vue.toDisplayString(cat.name), 11, ["onClick"]);
          }),
          128
          /* KEYED_FRAGMENT */
        ))
      ]),
      vue.createElementVNode(
        "scroll-view",
        {
          "scroll-y": "",
          class: "agent-list",
          onScrolltolower: _cache[3] || (_cache[3] = (...args) => $options.loadMore && $options.loadMore(...args))
        },
        [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.agentList, (agent) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "agent-card",
                key: agent.id,
                onClick: ($event) => $options.navigateToDetail(agent)
              }, [
                vue.createElementVNode("view", { class: "agent-header" }, [
                  vue.createElementVNode(
                    "view",
                    { class: "agent-icon" },
                    vue.toDisplayString(agent.icon || "🤖"),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode("view", { class: "agent-info" }, [
                    vue.createElementVNode(
                      "text",
                      { class: "agent-name" },
                      vue.toDisplayString(agent.name),
                      1
                      /* TEXT */
                    ),
                    vue.createElementVNode(
                      "text",
                      { class: "agent-category" },
                      vue.toDisplayString(agent.category_name || "通用"),
                      1
                      /* TEXT */
                    )
                  ]),
                  vue.createElementVNode(
                    "view",
                    {
                      class: vue.normalizeClass(["agent-status", agent.status])
                    },
                    [
                      vue.createElementVNode(
                        "text",
                        null,
                        vue.toDisplayString(agent.status === "active" ? "在线" : agent.status === "draft" ? "草稿" : "离线"),
                        1
                        /* TEXT */
                      )
                    ],
                    2
                    /* CLASS */
                  )
                ]),
                vue.createElementVNode(
                  "text",
                  { class: "agent-desc" },
                  vue.toDisplayString(agent.description),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode("view", { class: "agent-tags" }, [
                  (vue.openBlock(true), vue.createElementBlock(
                    vue.Fragment,
                    null,
                    vue.renderList(agent.tags || [], (tag, index) => {
                      return vue.openBlock(), vue.createElementBlock(
                        "text",
                        {
                          class: "tag",
                          key: index
                        },
                        vue.toDisplayString(tag),
                        1
                        /* TEXT */
                      );
                    }),
                    128
                    /* KEYED_FRAGMENT */
                  ))
                ]),
                vue.createElementVNode("view", { class: "agent-footer" }, [
                  vue.createElementVNode("view", { class: "stats" }, [
                    vue.createElementVNode(
                      "text",
                      { class: "stat-item" },
                      "💬 " + vue.toDisplayString(agent.chat_count || 0) + " 次对话",
                      1
                      /* TEXT */
                    ),
                    vue.createElementVNode(
                      "text",
                      { class: "stat-item" },
                      "👍 " + vue.toDisplayString(agent.likes || 0),
                      1
                      /* TEXT */
                    )
                  ]),
                  vue.createElementVNode("view", {
                    class: "action-btn",
                    onClick: vue.withModifiers(($event) => $options.quickChat(agent), ["stop"])
                  }, " 开始对话 ", 8, ["onClick"])
                ])
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          )),
          $data.loading ? (vue.openBlock(), vue.createElementBlock("view", {
            key: 0,
            class: "load-more"
          }, [
            vue.createElementVNode("view", { class: "loading-spinner" }),
            vue.createElementVNode("text", null, "加载中...")
          ])) : vue.createCommentVNode("v-if", true),
          !$data.loading && $data.agentList.length === 0 ? (vue.openBlock(), vue.createElementBlock("view", {
            key: 1,
            class: "empty-state"
          }, [
            vue.createElementVNode("text", { class: "empty-icon" }, "🤖"),
            vue.createElementVNode("text", { class: "empty-text" }, "暂无智能体"),
            vue.createElementVNode("text", { class: "empty-desc" }, "点击右下角 + 创建你的第一个智能体")
          ])) : vue.createCommentVNode("v-if", true)
        ],
        32
        /* NEED_HYDRATION */
      ),
      vue.createElementVNode("view", {
        class: "create-btn",
        onClick: _cache[4] || (_cache[4] = (...args) => $options.createAgent && $options.createAgent(...args))
      }, [
        vue.createElementVNode("text", null, "+")
      ])
    ]);
  }
  const PagesAgentsAgents = /* @__PURE__ */ _export_sfc(_sfc_main$f, [["render", _sfc_render$e], ["__scopeId", "data-v-d83f75da"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/agents/agents.vue"]]);
  const _sfc_main$e = {
    data() {
      return {
        agentId: null,
        agent: {},
        providerMap: {},
        isCollected: false,
        examples: []
      };
    },
    computed: {
      statusLabel() {
        const map = {
          active: "运行中",
          draft: "草稿",
          disabled: "已停用"
        };
        return map[this.agent.status] || "草稿";
      },
      providerName() {
        const providerId = this.agent.model_provider || "";
        return this.providerMap[providerId] || providerId || "未配置";
      }
    },
    onLoad(options) {
      this.agentId = options.id;
      this.loadPageData();
    },
    methods: {
      async loadPageData() {
        uni.showLoading({ title: "加载中..." });
        try {
          await Promise.all([this.loadProviders(), this.loadAgentDetail()]);
        } finally {
          uni.hideLoading();
        }
      },
      async loadProviders() {
        try {
          const res = await agentsApi.getProviders();
          if (res.success && res.data) {
            const map = {};
            res.data.forEach((item) => {
              map[item.id] = item.name || item.id;
            });
            this.providerMap = map;
          }
        } catch (error) {
          formatAppLog("error", "at pages/agent-detail/agent-detail.vue:130", "loadProviders failed", error);
        }
      },
      async loadAgentDetail() {
        try {
          const res = await agentsApi.getAgentDetail(this.agentId);
          if (res.success && res.agent) {
            const agent = { ...res.agent };
            if (typeof agent.tags === "string") {
              try {
                agent.tags = JSON.parse(agent.tags);
              } catch (e) {
                agent.tags = agent.tags.split(",").map((item) => item.trim()).filter(Boolean);
              }
            }
            if (!Array.isArray(agent.tags)) {
              agent.tags = [];
            }
            this.agent = agent;
            this.examples = this.buildExamples(agent);
          } else {
            uni.showToast({ title: res.error || "加载失败", icon: "none" });
          }
        } catch (error) {
          formatAppLog("error", "at pages/agent-detail/agent-detail.vue:158", "loadAgentDetail failed", error);
          uni.showToast({ title: "加载失败", icon: "none" });
        }
      },
      buildExamples(agent) {
        const category = agent.category || "通用";
        const map = {
          写作: ["请帮我润色这段文字。", "请给我一份文章大纲。", "请写一段产品介绍。"],
          编程: ["请解释这段代码。", "请帮我定位 bug。", "请写一个示例函数。"],
          分析: ["请总结这份数据的趋势。", "请帮我做一个对比分析。", "请输出关键结论。"],
          商务: ["请帮我写商务邮件。", "请整理会议纪要。", "请分析客户需求。"]
        };
        return map[category] || ["你好，请介绍一下你的能力。", "你可以帮我完成什么工作？", "请先给我一个使用建议。"];
      },
      useExample(example) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            getApp().globalData.currentAgent = this.agent;
            uni.$emit("setChatInput", example);
          }
        });
      },
      collectAgent() {
        this.isCollected = !this.isCollected;
        uni.showToast({
          title: this.isCollected ? "已收藏" : "已取消收藏",
          icon: "none"
        });
      },
      async startChat() {
        if (this.agent.status !== "active") {
          const res = await new Promise((resolve) => {
            uni.showModal({
              title: "提示",
              content: "当前智能体尚未部署，是否先部署？",
              success: resolve
            });
          });
          if (!res.confirm) {
            return;
          }
          const deployRes = await agentsApi.deployAgent(this.agentId);
          if (!deployRes.success) {
            uni.showToast({ title: deployRes.error || "部署失败", icon: "none" });
            return;
          }
          this.agent.status = "active";
        }
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            getApp().globalData.currentAgent = this.agent;
            uni.$emit("setChatInput", `你好，${this.agent.name}！`);
          }
        });
      }
    }
  };
  function _sfc_render$d(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "detail-page" }, [
      vue.createElementVNode("view", { class: "hero" }, [
        vue.createElementVNode(
          "view",
          { class: "hero-icon" },
          vue.toDisplayString($data.agent.icon || "🤖"),
          1
          /* TEXT */
        ),
        vue.createElementVNode(
          "text",
          { class: "hero-name" },
          vue.toDisplayString($data.agent.name || "智能体"),
          1
          /* TEXT */
        ),
        vue.createElementVNode(
          "text",
          { class: "hero-category" },
          vue.toDisplayString($data.agent.category || "通用"),
          1
          /* TEXT */
        ),
        vue.createElementVNode(
          "view",
          {
            class: vue.normalizeClass(["hero-status", $data.agent.status || "draft"])
          },
          [
            vue.createElementVNode(
              "text",
              null,
              vue.toDisplayString($options.statusLabel),
              1
              /* TEXT */
            )
          ],
          2
          /* CLASS */
        )
      ]),
      vue.createElementVNode("view", { class: "section-card" }, [
        vue.createElementVNode("text", { class: "section-title" }, "简介"),
        vue.createElementVNode(
          "text",
          { class: "section-text" },
          vue.toDisplayString($data.agent.description || "暂无简介"),
          1
          /* TEXT */
        )
      ]),
      $data.agent.tags && $data.agent.tags.length ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 0,
        class: "section-card"
      }, [
        vue.createElementVNode("text", { class: "section-title" }, "标签"),
        vue.createElementVNode("view", { class: "tag-list" }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.agent.tags, (tag) => {
              return vue.openBlock(), vue.createElementBlock(
                "text",
                {
                  key: tag,
                  class: "tag"
                },
                vue.toDisplayString(tag),
                1
                /* TEXT */
              );
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ])
      ])) : vue.createCommentVNode("v-if", true),
      vue.createElementVNode("view", { class: "section-card" }, [
        vue.createElementVNode("text", { class: "section-title" }, "模型配置"),
        vue.createElementVNode("view", { class: "info-row" }, [
          vue.createElementVNode("text", { class: "info-label" }, "模型"),
          vue.createElementVNode(
            "text",
            { class: "info-value strong" },
            vue.toDisplayString($data.agent.model_id || "未配置"),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", { class: "info-row" }, [
          vue.createElementVNode("text", { class: "info-label" }, "提供商"),
          vue.createElementVNode(
            "text",
            { class: "info-value" },
            vue.toDisplayString($options.providerName),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", { class: "info-row" }, [
          vue.createElementVNode("text", { class: "info-label" }, "Temperature"),
          vue.createElementVNode(
            "text",
            { class: "info-value" },
            vue.toDisplayString($data.agent.temperature || "0.7"),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", { class: "info-row" }, [
          vue.createElementVNode("text", { class: "info-label" }, "Max Tokens"),
          vue.createElementVNode(
            "text",
            { class: "info-value" },
            vue.toDisplayString($data.agent.max_tokens || 4096),
            1
            /* TEXT */
          )
        ])
      ]),
      $data.agent.welcome_message ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 1,
        class: "section-card"
      }, [
        vue.createElementVNode("text", { class: "section-title" }, "欢迎语"),
        vue.createElementVNode(
          "text",
          { class: "section-text" },
          vue.toDisplayString($data.agent.welcome_message),
          1
          /* TEXT */
        )
      ])) : vue.createCommentVNode("v-if", true),
      $data.agent.system_prompt ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 2,
        class: "section-card"
      }, [
        vue.createElementVNode("text", { class: "section-title" }, "系统提示词"),
        vue.createElementVNode(
          "text",
          { class: "section-text prompt" },
          vue.toDisplayString($data.agent.system_prompt),
          1
          /* TEXT */
        )
      ])) : vue.createCommentVNode("v-if", true),
      $data.examples.length ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 3,
        class: "section-card"
      }, [
        vue.createElementVNode("text", { class: "section-title" }, "示例问题"),
        (vue.openBlock(true), vue.createElementBlock(
          vue.Fragment,
          null,
          vue.renderList($data.examples, (example) => {
            return vue.openBlock(), vue.createElementBlock("view", {
              key: example,
              class: "example-item",
              onClick: ($event) => $options.useExample(example)
            }, [
              vue.createElementVNode(
                "text",
                { class: "example-text" },
                vue.toDisplayString(example),
                1
                /* TEXT */
              ),
              vue.createElementVNode("text", { class: "example-arrow" }, ">")
            ], 8, ["onClick"]);
          }),
          128
          /* KEYED_FRAGMENT */
        ))
      ])) : vue.createCommentVNode("v-if", true),
      vue.createElementVNode("view", { class: "footer-actions" }, [
        vue.createElementVNode(
          "view",
          {
            class: "btn secondary",
            onClick: _cache[0] || (_cache[0] = (...args) => $options.collectAgent && $options.collectAgent(...args))
          },
          vue.toDisplayString($data.isCollected ? "已收藏" : "收藏"),
          1
          /* TEXT */
        ),
        vue.createElementVNode(
          "view",
          {
            class: "btn primary",
            onClick: _cache[1] || (_cache[1] = (...args) => $options.startChat && $options.startChat(...args))
          },
          vue.toDisplayString($data.agent.status === "active" ? "开始对话" : "部署并对话"),
          1
          /* TEXT */
        )
      ])
    ]);
  }
  const PagesAgentDetailAgentDetail = /* @__PURE__ */ _export_sfc(_sfc_main$e, [["render", _sfc_render$d], ["__scopeId", "data-v-99e6a5cd"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/agent-detail/agent-detail.vue"]]);
  const ICON_OPTIONS = [
    { value: "🤖", label: "机器人" },
    { value: "💡", label: "灵感" },
    { value: "🧠", label: "思考" },
    { value: "📝", label: "写作" },
    { value: "💻", label: "代码" },
    { value: "📊", label: "分析" },
    { value: "🎨", label: "设计" },
    { value: "🔍", label: "搜索" },
    { value: "🚀", label: "效率" },
    { value: "⚡", label: "快速" },
    { value: "🛠️", label: "工具" },
    { value: "📚", label: "知识" },
    { value: "🎯", label: "目标" },
    { value: "💬", label: "沟通" },
    { value: "🌟", label: "通用" }
  ];
  const _sfc_main$d = {
    data() {
      return {
        form: {
          name: "",
          icon: "🤖",
          category: "通用",
          description: "",
          tags: [],
          model_provider: "",
          model_id: "",
          role_name: "",
          system_prompt: "",
          temperature: 0.7,
          max_tokens: 4096,
          welcome_message: "",
          capabilities: []
        },
        tagsInput: "",
        capabilitiesInput: "",
        iconKeyword: "",
        modelKeyword: "",
        iconOptions: ICON_OPTIONS,
        categoryOptions: ["通用", "写作", "编程", "分析", "创意", "教育", "商务", "生活", "娱乐"],
        categoryIndex: 0,
        onlineModels: [],
        expandedProviders: {}
      };
    },
    computed: {
      filteredIcons() {
        const keyword = this.iconKeyword.trim().toLowerCase();
        if (!keyword) {
          return this.iconOptions;
        }
        return this.iconOptions.filter(
          (item) => item.label.toLowerCase().includes(keyword) || item.value.includes(keyword)
        );
      },
      selectedIconLabel() {
        const found = this.iconOptions.find((item) => item.value === this.form.icon);
        return found ? found.label : "";
      },
      selectedModel() {
        return this.onlineModels.find(
          (item) => item.provider === this.form.model_provider && item.id === this.form.model_id
        ) || null;
      },
      groupedModels() {
        const keyword = this.modelKeyword.trim().toLowerCase();
        const groups = {};
        this.onlineModels.forEach((model) => {
          const match = !keyword || model.name.toLowerCase().includes(keyword) || model.providerName.toLowerCase().includes(keyword) || model.providerType.toLowerCase().includes(keyword);
          if (!match)
            return;
          if (!groups[model.provider]) {
            groups[model.provider] = {
              providerId: model.provider,
              providerName: model.providerName,
              providerType: model.providerType,
              models: []
            };
          }
          groups[model.provider].models.push(model);
        });
        return Object.values(groups).sort((a, b) => {
          if (a.providerId === this.form.model_provider)
            return -1;
          if (b.providerId === this.form.model_provider)
            return 1;
          return a.providerName.localeCompare(b.providerName);
        });
      }
    },
    onLoad() {
      this.loadModels();
    },
    methods: {
      async loadModels() {
        try {
          const providerRes = await agentsApi.getProviders();
          this.onlineModels = [];
          this.expandedProviders = {};
          if (providerRes.success && providerRes.data) {
            providerRes.data.forEach((provider) => {
              const providerModels = Array.isArray(provider.models) ? provider.models : [];
              this.expandedProviders[provider.id] = !!provider.is_default;
              providerModels.forEach((modelName) => {
                this.onlineModels.push({
                  id: modelName,
                  name: modelName,
                  provider: provider.id,
                  providerName: provider.name || provider.id,
                  providerType: provider.type || "api",
                  remark: this.getModelRemark(modelName)
                });
              });
              if (!this.form.model_provider && provider.is_default && providerModels.length > 0) {
                const defaultModel = provider.default_model || providerModels[0];
                this.form.model_provider = provider.id;
                this.form.model_id = defaultModel;
              }
            });
          }
        } catch (error) {
          formatAppLog("error", "at pages/agent-create/agent-create.vue:294", "loadModels failed:", error);
          uni.showToast({ title: "模型加载失败", icon: "none" });
        }
      },
      getModelRemark(name) {
        const lower = String(name || "").toLowerCase();
        if (lower.includes("vl") || lower.includes("vision"))
          return "图文理解";
        if (lower.includes("ocr"))
          return "OCR";
        if (lower.includes("image"))
          return "图像";
        if (lower.includes("video"))
          return "视频";
        if (lower.includes("r1"))
          return "推理模型";
        if (lower.includes("coder") || lower.includes("code"))
          return "代码生成";
        if (lower.includes("chat"))
          return "对话";
        if (lower.includes("turbo"))
          return "高速";
        if (lower.includes("max"))
          return "高性能";
        return "通用模型";
      },
      onCategoryChange(e) {
        this.categoryIndex = Number(e.detail.value);
        this.form.category = this.categoryOptions[this.categoryIndex];
      },
      onTempChange(e) {
        this.form.temperature = (Number(e.detail.value) / 100).toFixed(1);
      },
      selectIcon(icon) {
        this.form.icon = icon.value;
      },
      selectModel(model) {
        this.form.model_id = model.id;
        this.form.model_provider = model.provider;
      },
      toggleProviderGroup(providerId) {
        this.expandedProviders = {
          ...this.expandedProviders,
          [providerId]: !this.expandedProviders[providerId]
        };
      },
      isProviderExpanded(providerId) {
        return !!this.expandedProviders[providerId];
      },
      goBack() {
        uni.navigateBack();
      },
      buildPayload() {
        return {
          ...this.form,
          tags: this.tagsInput.split(/[,\n，]/).map((item) => item.trim()).filter(Boolean),
          capabilities: this.capabilitiesInput.split(/\n+/).map((item) => item.trim()).filter(Boolean)
        };
      },
      async saveAgent() {
        if (!this.form.name.trim()) {
          uni.showToast({ title: "请输入智能体名称", icon: "none" });
          return;
        }
        if (!this.form.model_id || !this.form.model_provider) {
          uni.showToast({ title: "请选择 AI 模型", icon: "none" });
          return;
        }
        if (!this.form.system_prompt.trim()) {
          uni.showToast({ title: "请输入系统提示词", icon: "none" });
          return;
        }
        uni.showLoading({ title: "保存中..." });
        try {
          const res = await agentsApi.createAgent(this.buildPayload());
          if (res.success) {
            uni.showToast({ title: "创建成功", icon: "success" });
            setTimeout(() => {
              uni.navigateBack();
            }, 800);
          } else {
            uni.showToast({ title: res.error || "创建失败", icon: "none" });
          }
        } catch (error) {
          formatAppLog("error", "at pages/agent-create/agent-create.vue:389", "saveAgent failed:", error);
          uni.showToast({ title: "创建失败", icon: "none" });
        } finally {
          uni.hideLoading();
        }
      }
    }
  };
  function _sfc_render$c(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "create-page" }, [
      vue.createElementVNode("view", { class: "nav-bar" }, [
        vue.createElementVNode("view", {
          class: "nav-back",
          onClick: _cache[0] || (_cache[0] = (...args) => $options.goBack && $options.goBack(...args))
        }, [
          vue.createElementVNode("text", { class: "nav-back-text" }, "返回")
        ]),
        vue.createElementVNode("text", { class: "nav-title" }, "创建智能体"),
        vue.createElementVNode("view", {
          class: "nav-save",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.saveAgent && $options.saveAgent(...args))
        }, "保存")
      ]),
      vue.createElementVNode("scroll-view", {
        "scroll-y": "",
        class: "page-scroll"
      }, [
        vue.createElementVNode("view", { class: "section-card" }, [
          vue.createElementVNode("text", { class: "section-title" }, "基本信息"),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "智能体名称 *"),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.form.name = $event),
                class: "input",
                placeholder: "给智能体起个名字"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.form.name,
                void 0,
                { trim: true }
              ]
            ])
          ]),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "图标"),
            vue.createElementVNode("view", { class: "icon-preview-card" }, [
              vue.createElementVNode(
                "view",
                { class: "icon-preview" },
                vue.toDisplayString($data.form.icon),
                1
                /* TEXT */
              ),
              vue.createElementVNode("view", { class: "icon-preview-info" }, [
                vue.createElementVNode(
                  "text",
                  { class: "icon-preview-name" },
                  vue.toDisplayString($options.selectedIconLabel || "未选择图标"),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode("text", { class: "icon-preview-desc" }, "点击下方图标即可切换")
              ])
            ]),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.iconKeyword = $event),
                class: "input input-compact",
                placeholder: "搜索图标，如：写作、代码、分析"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.iconKeyword,
                void 0,
                { trim: true }
              ]
            ]),
            vue.createElementVNode("view", { class: "icon-grid" }, [
              (vue.openBlock(true), vue.createElementBlock(
                vue.Fragment,
                null,
                vue.renderList($options.filteredIcons, (icon) => {
                  return vue.openBlock(), vue.createElementBlock("view", {
                    key: icon.value,
                    class: vue.normalizeClass(["icon-tile", { active: $data.form.icon === icon.value }]),
                    onClick: ($event) => $options.selectIcon(icon)
                  }, [
                    vue.createElementVNode(
                      "text",
                      { class: "icon-value" },
                      vue.toDisplayString(icon.value),
                      1
                      /* TEXT */
                    ),
                    vue.createElementVNode(
                      "text",
                      { class: "icon-label" },
                      vue.toDisplayString(icon.label),
                      1
                      /* TEXT */
                    )
                  ], 10, ["onClick"]);
                }),
                128
                /* KEYED_FRAGMENT */
              ))
            ])
          ]),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "分类"),
            vue.createElementVNode("picker", {
              mode: "selector",
              range: $data.categoryOptions,
              value: $data.categoryIndex,
              onChange: _cache[4] || (_cache[4] = (...args) => $options.onCategoryChange && $options.onCategoryChange(...args))
            }, [
              vue.createElementVNode("view", { class: "picker" }, [
                vue.createElementVNode(
                  "text",
                  null,
                  vue.toDisplayString($data.form.category || "选择分类"),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode("text", { class: "picker-arrow" }, ">")
              ])
            ], 40, ["range", "value"])
          ]),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "简介"),
            vue.withDirectives(vue.createElementVNode(
              "textarea",
              {
                "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.form.description = $event),
                class: "textarea",
                placeholder: "简要介绍智能体的定位和用途"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.form.description,
                void 0,
                { trim: true }
              ]
            ])
          ]),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "功能标签"),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.tagsInput = $event),
                class: "input",
                placeholder: "用逗号分隔，如：写作,代码,分析"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.tagsInput,
                void 0,
                { trim: true }
              ]
            ])
          ])
        ]),
        vue.createElementVNode("view", { class: "section-card" }, [
          vue.createElementVNode("text", { class: "section-title" }, "AI 模型设置"),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "选择模型 *"),
            $options.selectedModel ? (vue.openBlock(), vue.createElementBlock("view", {
              key: 0,
              class: "selected-model-card"
            }, [
              vue.createElementVNode("view", { class: "selected-model-main" }, [
                vue.createElementVNode(
                  "text",
                  { class: "selected-model-name" },
                  vue.toDisplayString($options.selectedModel.name),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode(
                  "text",
                  { class: "selected-model-provider" },
                  vue.toDisplayString($options.selectedModel.providerName),
                  1
                  /* TEXT */
                )
              ]),
              vue.createElementVNode(
                "view",
                { class: "selected-model-meta" },
                vue.toDisplayString($options.selectedModel.providerType),
                1
                /* TEXT */
              )
            ])) : vue.createCommentVNode("v-if", true),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.modelKeyword = $event),
                class: "input input-compact",
                placeholder: "搜索模型或提供商"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.modelKeyword,
                void 0,
                { trim: true }
              ]
            ]),
            vue.createElementVNode("view", { class: "model-selector" }, [
              $options.groupedModels.length === 0 ? (vue.openBlock(), vue.createElementBlock("view", {
                key: 0,
                class: "empty-state"
              }, [
                vue.createElementVNode("text", null, "暂无可用模型，请先在服务端配置在线 AI 提供商")
              ])) : vue.createCommentVNode("v-if", true),
              (vue.openBlock(true), vue.createElementBlock(
                vue.Fragment,
                null,
                vue.renderList($options.groupedModels, (group) => {
                  return vue.openBlock(), vue.createElementBlock("view", {
                    key: group.providerId,
                    class: "model-group"
                  }, [
                    vue.createElementVNode("view", {
                      class: "model-group-header",
                      onClick: ($event) => $options.toggleProviderGroup(group.providerId)
                    }, [
                      vue.createElementVNode("view", { class: "model-group-info" }, [
                        vue.createElementVNode(
                          "text",
                          { class: "model-group-name" },
                          vue.toDisplayString(group.providerName),
                          1
                          /* TEXT */
                        ),
                        vue.createElementVNode(
                          "text",
                          { class: "model-group-count" },
                          vue.toDisplayString(group.models.length) + " 个模型",
                          1
                          /* TEXT */
                        )
                      ]),
                      vue.createElementVNode(
                        "text",
                        { class: "model-group-toggle" },
                        vue.toDisplayString($options.isProviderExpanded(group.providerId) ? "收起" : "展开"),
                        1
                        /* TEXT */
                      )
                    ], 8, ["onClick"]),
                    $options.isProviderExpanded(group.providerId) ? (vue.openBlock(), vue.createElementBlock("view", {
                      key: 0,
                      class: "model-list"
                    }, [
                      (vue.openBlock(true), vue.createElementBlock(
                        vue.Fragment,
                        null,
                        vue.renderList(group.models, (model) => {
                          return vue.openBlock(), vue.createElementBlock("view", {
                            key: group.providerId + ":" + model.id,
                            class: vue.normalizeClass(["model-option", { active: $data.form.model_id === model.id && $data.form.model_provider === model.provider }]),
                            onClick: ($event) => $options.selectModel(model)
                          }, [
                            vue.createElementVNode("view", { class: "model-main" }, [
                              vue.createElementVNode(
                                "text",
                                { class: "model-name" },
                                vue.toDisplayString(model.name),
                                1
                                /* TEXT */
                              ),
                              vue.createElementVNode(
                                "text",
                                { class: "model-remark" },
                                vue.toDisplayString(model.remark),
                                1
                                /* TEXT */
                              )
                            ]),
                            $data.form.model_id === model.id && $data.form.model_provider === model.provider ? (vue.openBlock(), vue.createElementBlock("text", {
                              key: 0,
                              class: "model-check"
                            }, "✓")) : vue.createCommentVNode("v-if", true)
                          ], 10, ["onClick"]);
                        }),
                        128
                        /* KEYED_FRAGMENT */
                      ))
                    ])) : vue.createCommentVNode("v-if", true)
                  ]);
                }),
                128
                /* KEYED_FRAGMENT */
              ))
            ])
          ]),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "角色设定"),
            vue.withDirectives(vue.createElementVNode(
              "textarea",
              {
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.form.role_name = $event),
                class: "textarea textarea-sm",
                placeholder: "例如：专业写作助手、售前顾问、数据分析师"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.form.role_name,
                void 0,
                { trim: true }
              ]
            ])
          ]),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "系统提示词 *"),
            vue.withDirectives(vue.createElementVNode(
              "textarea",
              {
                "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $data.form.system_prompt = $event),
                class: "textarea textarea-lg",
                placeholder: "定义智能体应该如何回答、擅长什么、遵循什么限制"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.form.system_prompt,
                void 0,
                { trim: true }
              ]
            ]),
            vue.createElementVNode("text", { class: "hint" }, "建议写清楚身份、能力边界、回答风格和输出格式。")
          ]),
          vue.createElementVNode("view", { class: "form-row" }, [
            vue.createElementVNode("view", { class: "form-item form-item-half" }, [
              vue.createElementVNode("text", { class: "label" }, "Temperature"),
              vue.createElementVNode("slider", {
                value: $data.form.temperature * 100,
                min: "0",
                max: "200",
                "show-value": "",
                onChange: _cache[10] || (_cache[10] = (...args) => $options.onTempChange && $options.onTempChange(...args))
              }, null, 40, ["value"]),
              vue.createElementVNode(
                "text",
                { class: "hint" },
                vue.toDisplayString($data.form.temperature),
                1
                /* TEXT */
              )
            ]),
            vue.createElementVNode("view", { class: "form-item form-item-half" }, [
              vue.createElementVNode("text", { class: "label" }, "Max Tokens"),
              vue.withDirectives(vue.createElementVNode(
                "input",
                {
                  "onUpdate:modelValue": _cache[11] || (_cache[11] = ($event) => $data.form.max_tokens = $event),
                  class: "input",
                  type: "number",
                  placeholder: "4096"
                },
                null,
                512
                /* NEED_PATCH */
              ), [
                [vue.vModelText, $data.form.max_tokens]
              ])
            ])
          ])
        ]),
        vue.createElementVNode("view", { class: "section-card" }, [
          vue.createElementVNode("text", { class: "section-title" }, "高级设置"),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "欢迎语"),
            vue.withDirectives(vue.createElementVNode(
              "input",
              {
                "onUpdate:modelValue": _cache[12] || (_cache[12] = ($event) => $data.form.welcome_message = $event),
                class: "input",
                placeholder: "用户第一次打开时看到的欢迎语"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.form.welcome_message,
                void 0,
                { trim: true }
              ]
            ])
          ]),
          vue.createElementVNode("view", { class: "form-item" }, [
            vue.createElementVNode("text", { class: "label" }, "能力说明"),
            vue.withDirectives(vue.createElementVNode(
              "textarea",
              {
                "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $data.capabilitiesInput = $event),
                class: "textarea",
                placeholder: "每行一条，例如：\\n擅长写作\\n能够总结文档\\n支持代码解释"
              },
              null,
              512
              /* NEED_PATCH */
            ), [
              [
                vue.vModelText,
                $data.capabilitiesInput,
                void 0,
                { trim: true }
              ]
            ])
          ])
        ]),
        vue.createElementVNode("view", { class: "bottom-space" })
      ])
    ]);
  }
  const PagesAgentCreateAgentCreate = /* @__PURE__ */ _export_sfc(_sfc_main$d, [["render", _sfc_render$c], ["__scopeId", "data-v-b77c2db1"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/agent-create/agent-create.vue"]]);
  const _sfc_main$c = {
    data() {
      return {
        userInfo: {},
        stats: {
          chatCount: 0,
          tokenUsed: 0,
          days: 0
        }
      };
    },
    onShow() {
      this.loadUserInfo();
    },
    methods: {
      async loadUserInfo() {
        this.userInfo = uni.getStorageSync("userInfo") || {};
        try {
          const profileRes = await userApi.getUserInfo();
          if ((profileRes.success || profileRes.status === "success") && profileRes.data) {
            this.userInfo = {
              ...this.userInfo,
              ...profileRes.data
            };
            uni.setStorageSync("userInfo", this.userInfo);
          }
        } catch (error) {
          formatAppLog("error", "at pages/mine/mine.vue:90", "loadUserInfo profile failed", error);
        }
        try {
          const statsRes = await userApi.getUserStats();
          if ((statsRes.success || statsRes.status === "success") && statsRes.data) {
            this.stats = {
              chatCount: statsRes.data.total_calls || 0,
              tokenUsed: statsRes.data.total_tokens || 0,
              days: this.calculateDays()
            };
          }
        } catch (error) {
          formatAppLog("error", "at pages/mine/mine.vue:103", "loadUserInfo stats failed", error);
        }
      },
      calculateDays() {
        const createdAt = this.userInfo.created_at || uni.getStorageSync("registerTime");
        if (!createdAt)
          return 1;
        const days = Math.floor((Date.now() - new Date(createdAt).getTime()) / (1e3 * 60 * 60 * 24));
        return Math.max(1, days);
      },
      editProfile() {
        uni.navigateTo({ url: "/pages/settings/profile" });
      },
      openChat() {
        uni.switchTab({ url: "/pages/chat/chat" });
      },
      showCollections() {
        uni.navigateTo({ url: "/pages/collections/collections" });
      },
      showHistory() {
        uni.navigateTo({ url: "/pages/history/history" });
      },
      showSettings() {
        uni.navigateTo({ url: "/pages/settings/settings" });
      },
      showHelp() {
        uni.navigateTo({ url: "/pages/help/help" });
      },
      async handleLogout() {
        uni.showModal({
          title: "确认退出",
          content: "确定要退出登录吗？",
          success: async (res) => {
            if (!res.confirm)
              return;
            await userApi.logout();
            uni.redirectTo({ url: "/pages/login/login" });
          }
        });
      }
    }
  };
  function _sfc_render$b(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "page" }, [
      vue.createElementVNode("view", { class: "hero" }, [
        vue.createElementVNode("view", { class: "avatar" }, [
          vue.createElementVNode(
            "text",
            { class: "avatar-text" },
            vue.toDisplayString(($data.userInfo.username || "U").slice(0, 1).toUpperCase()),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", { class: "user-block" }, [
          vue.createElementVNode(
            "text",
            { class: "username" },
            vue.toDisplayString($data.userInfo.username || "未登录"),
            1
            /* TEXT */
          ),
          vue.createElementVNode(
            "text",
            { class: "subtext" },
            "ID: " + vue.toDisplayString($data.userInfo.id || "--"),
            1
            /* TEXT */
          ),
          vue.createElementVNode(
            "text",
            { class: "subtext" },
            vue.toDisplayString($data.userInfo.email || "未绑定邮箱"),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", {
          class: "edit-btn",
          onClick: _cache[0] || (_cache[0] = (...args) => $options.editProfile && $options.editProfile(...args))
        }, "编辑")
      ]),
      vue.createElementVNode("view", { class: "stats-card" }, [
        vue.createElementVNode("view", { class: "stat" }, [
          vue.createElementVNode(
            "text",
            { class: "stat-value" },
            vue.toDisplayString($data.stats.chatCount),
            1
            /* TEXT */
          ),
          vue.createElementVNode("text", { class: "stat-label" }, "对话次数")
        ]),
        vue.createElementVNode("view", { class: "stat" }, [
          vue.createElementVNode(
            "text",
            { class: "stat-value" },
            vue.toDisplayString($data.stats.tokenUsed),
            1
            /* TEXT */
          ),
          vue.createElementVNode("text", { class: "stat-label" }, "Token")
        ]),
        vue.createElementVNode("view", { class: "stat" }, [
          vue.createElementVNode(
            "text",
            { class: "stat-value" },
            vue.toDisplayString($data.stats.days),
            1
            /* TEXT */
          ),
          vue.createElementVNode("text", { class: "stat-label" }, "使用天数")
        ])
      ]),
      vue.createElementVNode("view", { class: "menu-card" }, [
        vue.createElementVNode("view", {
          class: "menu-item",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.openChat && $options.openChat(...args))
        }, [
          vue.createElementVNode("text", { class: "menu-title" }, "我的对话"),
          vue.createElementVNode("text", { class: "menu-arrow" }, ">")
        ]),
        vue.createElementVNode("view", {
          class: "menu-item",
          onClick: _cache[2] || (_cache[2] = (...args) => $options.showCollections && $options.showCollections(...args))
        }, [
          vue.createElementVNode("text", { class: "menu-title" }, "我的收藏"),
          vue.createElementVNode("text", { class: "menu-arrow" }, ">")
        ]),
        vue.createElementVNode("view", {
          class: "menu-item",
          onClick: _cache[3] || (_cache[3] = (...args) => $options.showHistory && $options.showHistory(...args))
        }, [
          vue.createElementVNode("text", { class: "menu-title" }, "历史记录"),
          vue.createElementVNode("text", { class: "menu-arrow" }, ">")
        ]),
        vue.createElementVNode("view", {
          class: "menu-item",
          onClick: _cache[4] || (_cache[4] = (...args) => $options.showSettings && $options.showSettings(...args))
        }, [
          vue.createElementVNode("text", { class: "menu-title" }, "设置"),
          vue.createElementVNode("text", { class: "menu-arrow" }, ">")
        ]),
        vue.createElementVNode("view", {
          class: "menu-item",
          onClick: _cache[5] || (_cache[5] = (...args) => $options.showHelp && $options.showHelp(...args))
        }, [
          vue.createElementVNode("text", { class: "menu-title" }, "帮助中心"),
          vue.createElementVNode("text", { class: "menu-arrow" }, ">")
        ])
      ]),
      vue.createElementVNode("view", {
        class: "logout-btn",
        onClick: _cache[6] || (_cache[6] = (...args) => $options.handleLogout && $options.handleLogout(...args))
      }, "退出登录")
    ]);
  }
  const PagesMineMine = /* @__PURE__ */ _export_sfc(_sfc_main$c, [["render", _sfc_render$b], ["__scopeId", "data-v-7c2ebfa5"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/mine/mine.vue"]]);
  const _sfc_main$b = {
    data() {
      return {
        currentTab: "agents",
        agentCollections: [],
        chatCollections: [],
        modelCollections: []
      };
    },
    onShow() {
      this.loadCollections();
    },
    methods: {
      changeTab(tab) {
        this.currentTab = tab;
      },
      loadCollections() {
        this.agentCollections = uni.getStorageSync("collectedAgents") || [];
        this.chatCollections = uni.getStorageSync("collectedChats") || [];
        this.modelCollections = uni.getStorageSync("collectedModels") || [];
      },
      removeCollection(type, id) {
        uni.showModal({
          title: "确认取消收藏",
          content: "确定要取消收藏吗？",
          success: (res) => {
            if (res.confirm) {
              if (type === "agent") {
                this.agentCollections = this.agentCollections.filter((item) => item.id !== id);
                uni.setStorageSync("collectedAgents", this.agentCollections);
              } else if (type === "chat") {
                this.chatCollections.splice(id, 1);
                uni.setStorageSync("collectedChats", this.chatCollections);
              } else if (type === "model") {
                this.modelCollections.splice(id, 1);
                uni.setStorageSync("collectedModels", this.modelCollections);
              }
              uni.showToast({ title: "已取消收藏", icon: "success" });
            }
          }
        });
      },
      navigateTo(url) {
        uni.navigateTo({ url });
      },
      continueChat(chat) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            uni.$emit("loadChatHistory", chat);
          }
        });
      },
      selectModel(model) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            uni.$emit("selectModel", model);
          }
        });
      }
    }
  };
  function _sfc_render$a(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "collections-container" }, [
      vue.createElementVNode("view", { class: "category-tabs" }, [
        vue.createElementVNode(
          "view",
          {
            class: vue.normalizeClass(["tab-item", { active: $data.currentTab === "agents" }]),
            onClick: _cache[0] || (_cache[0] = ($event) => $options.changeTab("agents"))
          },
          " 智能体 ",
          2
          /* CLASS */
        ),
        vue.createElementVNode(
          "view",
          {
            class: vue.normalizeClass(["tab-item", { active: $data.currentTab === "chats" }]),
            onClick: _cache[1] || (_cache[1] = ($event) => $options.changeTab("chats"))
          },
          " 对话 ",
          2
          /* CLASS */
        ),
        vue.createElementVNode(
          "view",
          {
            class: vue.normalizeClass(["tab-item", { active: $data.currentTab === "models" }]),
            onClick: _cache[2] || (_cache[2] = ($event) => $options.changeTab("models"))
          },
          " 模型 ",
          2
          /* CLASS */
        )
      ]),
      vue.createElementVNode("scroll-view", {
        "scroll-y": "",
        class: "collections-list"
      }, [
        $data.currentTab === "agents" ? (vue.openBlock(), vue.createElementBlock("view", { key: 0 }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.agentCollections, (item) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "collection-item",
                key: item.id,
                onClick: ($event) => $options.navigateTo("/pages/agent-detail/agent-detail?id=" + item.id)
              }, [
                vue.createElementVNode(
                  "view",
                  { class: "item-icon" },
                  vue.toDisplayString(item.icon),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode("view", { class: "item-info" }, [
                  vue.createElementVNode(
                    "text",
                    { class: "item-name" },
                    vue.toDisplayString(item.name),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode(
                    "text",
                    { class: "item-desc" },
                    vue.toDisplayString(item.description),
                    1
                    /* TEXT */
                  )
                ]),
                vue.createElementVNode("view", {
                  class: "item-action",
                  onClick: vue.withModifiers(($event) => $options.removeCollection("agent", item.id), ["stop"])
                }, [
                  vue.createElementVNode("text", { class: "remove-btn" }, "取消收藏")
                ], 8, ["onClick"])
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          )),
          $data.agentCollections.length === 0 ? (vue.openBlock(), vue.createElementBlock("view", {
            key: 0,
            class: "empty-state"
          }, [
            vue.createElementVNode("text", { class: "empty-icon" }, "🤖"),
            vue.createElementVNode("text", { class: "empty-text" }, "暂无收藏的智能体"),
            vue.createElementVNode("text", { class: "empty-desc" }, "去智能体页面发现更多")
          ])) : vue.createCommentVNode("v-if", true)
        ])) : vue.createCommentVNode("v-if", true),
        $data.currentTab === "chats" ? (vue.openBlock(), vue.createElementBlock("view", { key: 1 }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.chatCollections, (item, index) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "collection-item",
                key: index,
                onClick: ($event) => $options.continueChat(item)
              }, [
                vue.createElementVNode("view", { class: "item-icon" }, "💬"),
                vue.createElementVNode("view", { class: "item-info" }, [
                  vue.createElementVNode(
                    "text",
                    { class: "item-name" },
                    vue.toDisplayString(item.title),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode(
                    "text",
                    { class: "item-desc" },
                    vue.toDisplayString(item.time),
                    1
                    /* TEXT */
                  )
                ]),
                vue.createElementVNode("view", {
                  class: "item-action",
                  onClick: vue.withModifiers(($event) => $options.removeCollection("chat", index), ["stop"])
                }, [
                  vue.createElementVNode("text", { class: "remove-btn" }, "取消收藏")
                ], 8, ["onClick"])
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          )),
          $data.chatCollections.length === 0 ? (vue.openBlock(), vue.createElementBlock("view", {
            key: 0,
            class: "empty-state"
          }, [
            vue.createElementVNode("text", { class: "empty-icon" }, "💬"),
            vue.createElementVNode("text", { class: "empty-text" }, "暂无收藏的对话"),
            vue.createElementVNode("text", { class: "empty-desc" }, "在聊天时长按收藏")
          ])) : vue.createCommentVNode("v-if", true)
        ])) : vue.createCommentVNode("v-if", true),
        $data.currentTab === "models" ? (vue.openBlock(), vue.createElementBlock("view", { key: 2 }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.modelCollections, (item, index) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "collection-item",
                key: index,
                onClick: ($event) => $options.selectModel(item)
              }, [
                vue.createElementVNode("view", { class: "item-icon" }, "🧠"),
                vue.createElementVNode("view", { class: "item-info" }, [
                  vue.createElementVNode(
                    "text",
                    { class: "item-name" },
                    vue.toDisplayString(item.name),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode(
                    "text",
                    { class: "item-desc" },
                    vue.toDisplayString(item.remark),
                    1
                    /* TEXT */
                  )
                ]),
                vue.createElementVNode("view", {
                  class: "item-action",
                  onClick: vue.withModifiers(($event) => $options.removeCollection("model", index), ["stop"])
                }, [
                  vue.createElementVNode("text", { class: "remove-btn" }, "取消收藏")
                ], 8, ["onClick"])
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          )),
          $data.modelCollections.length === 0 ? (vue.openBlock(), vue.createElementBlock("view", {
            key: 0,
            class: "empty-state"
          }, [
            vue.createElementVNode("text", { class: "empty-icon" }, "🧠"),
            vue.createElementVNode("text", { class: "empty-text" }, "暂无收藏的模型"),
            vue.createElementVNode("text", { class: "empty-desc" }, "在模型选择时收藏")
          ])) : vue.createCommentVNode("v-if", true)
        ])) : vue.createCommentVNode("v-if", true)
      ])
    ]);
  }
  const PagesCollectionsCollections = /* @__PURE__ */ _export_sfc(_sfc_main$b, [["render", _sfc_render$a], ["__scopeId", "data-v-23e508ae"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/collections/collections.vue"]]);
  const _sfc_main$a = {
    data() {
      return {
        searchKeyword: "",
        historyList: [],
        filteredHistory: []
      };
    },
    onShow() {
      this.loadHistory();
    },
    methods: {
      loadHistory() {
        this.historyList = uni.getStorageSync("chatHistory") || [];
        this.filteredHistory = this.historyList;
      },
      filterHistory() {
        if (!this.searchKeyword) {
          this.filteredHistory = this.historyList;
          return;
        }
        const keyword = this.searchKeyword.toLowerCase();
        this.filteredHistory = this.historyList.filter(
          (item) => item.title.toLowerCase().includes(keyword) || item.preview.toLowerCase().includes(keyword)
        );
      },
      getTypeLabel(type) {
        const labels = {
          "chat": "对话",
          "agent": "智能体",
          "writing": "写作",
          "code": "代码",
          "analysis": "分析"
        };
        return labels[type] || "对话";
      },
      continueChat(chat) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            uni.$emit("loadChatHistory", chat);
          }
        });
      },
      deleteItem(index) {
        uni.showModal({
          title: "确认删除",
          content: "确定要删除这条记录吗？",
          success: (res) => {
            if (res.confirm) {
              this.historyList.splice(index, 1);
              uni.setStorageSync("chatHistory", this.historyList);
              this.filterHistory();
              uni.showToast({ title: "已删除", icon: "success" });
            }
          }
        });
      },
      collectItem(item) {
        const collections = uni.getStorageSync("collectedChats") || [];
        const exists = collections.find((c) => c.title === item.title);
        if (exists) {
          uni.showToast({ title: "已收藏", icon: "none" });
          return;
        }
        collections.unshift(item);
        uni.setStorageSync("collectedChats", collections);
        uni.showToast({ title: "收藏成功", icon: "success" });
      },
      clearAll() {
        uni.showModal({
          title: "确认清空",
          content: "确定要清空所有历史记录吗？此操作不可恢复",
          success: (res) => {
            if (res.confirm) {
              this.historyList = [];
              uni.setStorageSync("chatHistory", []);
              this.filteredHistory = [];
              uni.showToast({ title: "已清空", icon: "success" });
            }
          }
        });
      },
      showFilter() {
        uni.showActionSheet({
          itemList: ["全部", "对话", "智能体", "写作", "代码"],
          success: (res) => {
            const types = ["all", "chat", "agent", "writing", "code"];
            const type = types[res.tapIndex];
            if (type === "all") {
              this.filteredHistory = this.historyList;
            } else {
              this.filteredHistory = this.historyList.filter((item) => item.type === type);
            }
          }
        });
      },
      loadMore() {
      }
    }
  };
  function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "history-container" }, [
      vue.createElementVNode("view", { class: "filter-bar" }, [
        vue.createElementVNode("view", { class: "search-box" }, [
          vue.createElementVNode("text", { class: "search-icon" }, "🔍"),
          vue.withDirectives(vue.createElementVNode(
            "input",
            {
              type: "text",
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.searchKeyword = $event),
              placeholder: "搜索历史记录...",
              "placeholder-class": "placeholder",
              onInput: _cache[1] || (_cache[1] = (...args) => $options.filterHistory && $options.filterHistory(...args))
            },
            null,
            544
            /* NEED_HYDRATION, NEED_PATCH */
          ), [
            [vue.vModelText, $data.searchKeyword]
          ])
        ]),
        vue.createElementVNode("view", {
          class: "filter-btn",
          onClick: _cache[2] || (_cache[2] = (...args) => $options.showFilter && $options.showFilter(...args))
        }, [
          vue.createElementVNode("text", null, "筛选")
        ])
      ]),
      vue.createElementVNode(
        "scroll-view",
        {
          "scroll-y": "",
          class: "history-list",
          onScrolltolower: _cache[3] || (_cache[3] = (...args) => $options.loadMore && $options.loadMore(...args))
        },
        [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.filteredHistory, (item, index) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "history-item",
                key: index,
                onClick: ($event) => $options.continueChat(item)
              }, [
                vue.createElementVNode("view", { class: "item-header" }, [
                  vue.createElementVNode(
                    "view",
                    {
                      class: vue.normalizeClass(["item-type", item.type])
                    },
                    [
                      vue.createElementVNode(
                        "text",
                        null,
                        vue.toDisplayString($options.getTypeLabel(item.type)),
                        1
                        /* TEXT */
                      )
                    ],
                    2
                    /* CLASS */
                  ),
                  vue.createElementVNode(
                    "text",
                    { class: "item-time" },
                    vue.toDisplayString(item.time),
                    1
                    /* TEXT */
                  )
                ]),
                vue.createElementVNode("view", { class: "item-content" }, [
                  vue.createElementVNode(
                    "text",
                    { class: "item-title" },
                    vue.toDisplayString(item.title),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode(
                    "text",
                    { class: "item-preview" },
                    vue.toDisplayString(item.preview),
                    1
                    /* TEXT */
                  )
                ]),
                vue.createElementVNode("view", { class: "item-footer" }, [
                  vue.createElementVNode(
                    "text",
                    { class: "item-model" },
                    "模型: " + vue.toDisplayString(item.model || "默认"),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode("view", { class: "item-actions" }, [
                    vue.createElementVNode("text", {
                      class: "action-btn",
                      onClick: vue.withModifiers(($event) => $options.deleteItem(index), ["stop"])
                    }, "删除", 8, ["onClick"]),
                    vue.createElementVNode("text", {
                      class: "action-btn primary",
                      onClick: vue.withModifiers(($event) => $options.collectItem(item), ["stop"])
                    }, "收藏", 8, ["onClick"])
                  ])
                ])
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          )),
          $data.filteredHistory.length === 0 ? (vue.openBlock(), vue.createElementBlock("view", {
            key: 0,
            class: "empty-state"
          }, [
            vue.createElementVNode("text", { class: "empty-icon" }, "📋"),
            vue.createElementVNode("text", { class: "empty-text" }, "暂无历史记录"),
            vue.createElementVNode("text", { class: "empty-desc" }, "开始对话后会自动保存")
          ])) : vue.createCommentVNode("v-if", true)
        ],
        32
        /* NEED_HYDRATION */
      ),
      $data.historyList.length > 0 ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 0,
        class: "clear-section"
      }, [
        vue.createElementVNode("view", {
          class: "clear-btn",
          onClick: _cache[4] || (_cache[4] = (...args) => $options.clearAll && $options.clearAll(...args))
        }, [
          vue.createElementVNode("text", null, "清空所有历史")
        ])
      ])) : vue.createCommentVNode("v-if", true)
    ]);
  }
  const PagesHistoryHistory = /* @__PURE__ */ _export_sfc(_sfc_main$a, [["render", _sfc_render$9], ["__scopeId", "data-v-b2d018fa"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/history/history.vue"]]);
  const _sfc_main$9 = {
    data() {
      return {
        userInfo: {},
        settings: {
          defaultModel: ""
        },
        cacheSize: "0 MB",
        availableModels: []
      };
    },
    onShow() {
      this.loadUserInfo();
      this.loadSettings();
      this.calcCacheSize();
      this.loadAvailableModels();
    },
    methods: {
      async loadUserInfo() {
        this.userInfo = uni.getStorageSync("userInfo") || {};
        try {
          const res = await userApi.getUserInfo();
          if ((res.success || res.status === "success") && res.data) {
            this.userInfo = {
              ...this.userInfo,
              ...res.data
            };
            uni.setStorageSync("userInfo", this.userInfo);
          }
        } catch (error) {
          formatAppLog("error", "at pages/settings/settings.vue:75", "settings loadUserInfo failed", error);
        }
      },
      loadSettings() {
        const saved = uni.getStorageSync("appSettings");
        if (saved) {
          this.settings = { ...this.settings, ...saved };
        }
      },
      saveSettings() {
        uni.setStorageSync("appSettings", this.settings);
      },
      calcCacheSize() {
        const keys = uni.getStorageInfoSync().keys || [];
        let size = 0;
        keys.forEach((key) => {
          const item = uni.getStorageSync(key);
          size += JSON.stringify(item || "").length;
        });
        this.cacheSize = (size / 1024 / 1024).toFixed(2) + " MB";
      },
      editProfile() {
        uni.navigateTo({ url: "/pages/settings/profile" });
      },
      changePassword() {
        uni.navigateTo({ url: "/pages/settings/password" });
      },
      bindEmail() {
        uni.navigateTo({ url: "/pages/settings/email" });
      },
      clearCache() {
        uni.showModal({
          title: "清理缓存",
          content: "确定要清理缓存吗？",
          success: (res) => {
            if (!res.confirm)
              return;
            const userInfo = uni.getStorageSync("userInfo");
            const appSettings = uni.getStorageSync("appSettings");
            const token = uni.getStorageSync("token");
            const registerTime = uni.getStorageSync("registerTime");
            uni.clearStorageSync();
            if (token)
              uni.setStorageSync("token", token);
            if (userInfo)
              uni.setStorageSync("userInfo", userInfo);
            if (appSettings)
              uni.setStorageSync("appSettings", appSettings);
            if (registerTime)
              uni.setStorageSync("registerTime", registerTime);
            this.calcCacheSize();
            uni.showToast({ title: "清理完成", icon: "success" });
          }
        });
      },
      async loadAvailableModels() {
        try {
          const providerRes = await agentsApi.getProviders();
          const models = [];
          if (providerRes.success && providerRes.data) {
            providerRes.data.forEach((provider) => {
              ;
              (provider.models || []).forEach((modelName) => {
                models.push({
                  id: `${provider.id}:${modelName}`,
                  name: `${modelName} (${provider.name})`
                });
              });
            });
          }
          this.availableModels = models;
        } catch (error) {
          formatAppLog("error", "at pages/settings/settings.vue:149", "loadAvailableModels failed", error);
        }
      },
      setDefaultModel() {
        if (!this.availableModels.length) {
          uni.showToast({ title: "暂无可用模型", icon: "none" });
          return;
        }
        const itemList = ["自动选择", ...this.availableModels.map((item) => item.name)];
        uni.showActionSheet({
          itemList,
          success: (res) => {
            this.settings.defaultModel = res.tapIndex === 0 ? "" : this.availableModels[res.tapIndex - 1].name;
            this.saveSettings();
          }
        });
      },
      showPrivacy() {
        uni.navigateTo({ url: "/pages/settings/privacy" });
      },
      showTerms() {
        uni.navigateTo({ url: "/pages/settings/terms" });
      }
    }
  };
  function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "page" }, [
      vue.createElementVNode("view", { class: "card" }, [
        vue.createElementVNode("view", {
          class: "row",
          onClick: _cache[0] || (_cache[0] = (...args) => $options.editProfile && $options.editProfile(...args))
        }, [
          vue.createElementVNode("text", { class: "label" }, "个人资料"),
          vue.createElementVNode(
            "text",
            { class: "value" },
            vue.toDisplayString($data.userInfo.username || "未设置"),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", {
          class: "row",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.changePassword && $options.changePassword(...args))
        }, [
          vue.createElementVNode("text", { class: "label" }, "修改密码"),
          vue.createElementVNode("text", { class: "value" }, ">")
        ]),
        vue.createElementVNode("view", {
          class: "row",
          onClick: _cache[2] || (_cache[2] = (...args) => $options.bindEmail && $options.bindEmail(...args))
        }, [
          vue.createElementVNode("text", { class: "label" }, "绑定邮箱"),
          vue.createElementVNode(
            "text",
            { class: "value" },
            vue.toDisplayString($data.userInfo.email || "未绑定"),
            1
            /* TEXT */
          )
        ])
      ]),
      vue.createElementVNode("view", { class: "card" }, [
        vue.createElementVNode("view", {
          class: "row",
          onClick: _cache[3] || (_cache[3] = (...args) => $options.setDefaultModel && $options.setDefaultModel(...args))
        }, [
          vue.createElementVNode("text", { class: "label" }, "默认模型"),
          vue.createElementVNode(
            "text",
            { class: "value ellipsis" },
            vue.toDisplayString($data.settings.defaultModel || "自动选择"),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", {
          class: "row",
          onClick: _cache[4] || (_cache[4] = (...args) => $options.clearCache && $options.clearCache(...args))
        }, [
          vue.createElementVNode("text", { class: "label" }, "清理缓存"),
          vue.createElementVNode(
            "text",
            { class: "value" },
            vue.toDisplayString($data.cacheSize),
            1
            /* TEXT */
          )
        ]),
        vue.createElementVNode("view", {
          class: "row",
          onClick: _cache[5] || (_cache[5] = (...args) => $options.showPrivacy && $options.showPrivacy(...args))
        }, [
          vue.createElementVNode("text", { class: "label" }, "隐私政策"),
          vue.createElementVNode("text", { class: "value" }, ">")
        ]),
        vue.createElementVNode("view", {
          class: "row",
          onClick: _cache[6] || (_cache[6] = (...args) => $options.showTerms && $options.showTerms(...args))
        }, [
          vue.createElementVNode("text", { class: "label" }, "用户协议"),
          vue.createElementVNode("text", { class: "value" }, ">")
        ])
      ])
    ]);
  }
  const PagesSettingsSettings = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["render", _sfc_render$8], ["__scopeId", "data-v-7fad0a1c"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/settings/settings.vue"]]);
  const _sfc_main$8 = {
    data() {
      return {
        form: {
          username: "",
          email: ""
        }
      };
    },
    async onLoad() {
      const userInfo = uni.getStorageSync("userInfo") || {};
      this.form.username = userInfo.username || "";
      this.form.email = userInfo.email || "";
      try {
        const res = await userApi.getUserInfo();
        if ((res.success || res.status === "success") && res.data) {
          this.form.username = res.data.username || "";
          this.form.email = res.data.email || "";
        }
      } catch (error) {
        formatAppLog("error", "at pages/settings/profile.vue:36", "profile load failed", error);
      }
    },
    methods: {
      async save() {
        if (!this.form.username.trim()) {
          uni.showToast({ title: "请输入用户名", icon: "none" });
          return;
        }
        const res = await userApi.updateProfile(this.form);
        if (res.status === "success" || res.success) {
          await userApi.syncCurrentUser();
          uni.showToast({ title: "已保存", icon: "success" });
        } else {
          uni.showToast({ title: res.message || "保存失败", icon: "none" });
        }
      }
    }
  };
  function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "page" }, [
      vue.createElementVNode("view", { class: "card" }, [
        vue.createElementVNode("text", { class: "label" }, "用户名"),
        vue.withDirectives(vue.createElementVNode(
          "input",
          {
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.form.username = $event),
            class: "input",
            placeholder: "请输入用户名"
          },
          null,
          512
          /* NEED_PATCH */
        ), [
          [vue.vModelText, $data.form.username]
        ]),
        vue.createElementVNode("text", { class: "hint" }, "用户名会同步到服务器。"),
        vue.createElementVNode("view", {
          class: "btn",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.save && $options.save(...args))
        }, "保存")
      ])
    ]);
  }
  const PagesSettingsProfile = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$7], ["__scopeId", "data-v-824841f3"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/settings/profile.vue"]]);
  const _sfc_main$7 = {
    data() {
      return {
        oldPassword: "",
        newPassword: "",
        confirmPassword: ""
      };
    },
    methods: {
      async save() {
        if (!this.oldPassword || !this.newPassword) {
          uni.showToast({ title: "请填写完整", icon: "none" });
          return;
        }
        if (this.newPassword !== this.confirmPassword) {
          uni.showToast({ title: "两次密码不一致", icon: "none" });
          return;
        }
        const res = await userApi.changePassword({
          old_password: this.oldPassword,
          new_password: this.newPassword
        });
        if (res.status === "success" || res.success) {
          uni.showToast({ title: "密码修改成功", icon: "success" });
          setTimeout(() => uni.navigateBack(), 800);
        } else {
          uni.showToast({ title: res.message || "修改失败", icon: "none" });
        }
      }
    }
  };
  function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "page" }, [
      vue.createElementVNode("view", { class: "card" }, [
        vue.createElementVNode("text", { class: "label" }, "旧密码"),
        vue.withDirectives(vue.createElementVNode(
          "input",
          {
            password: "",
            class: "input",
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.oldPassword = $event),
            placeholder: "请输入旧密码"
          },
          null,
          512
          /* NEED_PATCH */
        ), [
          [vue.vModelText, $data.oldPassword]
        ]),
        vue.createElementVNode("text", { class: "label" }, "新密码"),
        vue.withDirectives(vue.createElementVNode(
          "input",
          {
            password: "",
            class: "input",
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.newPassword = $event),
            placeholder: "请输入新密码"
          },
          null,
          512
          /* NEED_PATCH */
        ), [
          [vue.vModelText, $data.newPassword]
        ]),
        vue.createElementVNode("text", { class: "label" }, "确认新密码"),
        vue.withDirectives(vue.createElementVNode(
          "input",
          {
            password: "",
            class: "input",
            "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.confirmPassword = $event),
            placeholder: "请再次输入新密码"
          },
          null,
          512
          /* NEED_PATCH */
        ), [
          [vue.vModelText, $data.confirmPassword]
        ]),
        vue.createElementVNode("view", {
          class: "btn",
          onClick: _cache[3] || (_cache[3] = (...args) => $options.save && $options.save(...args))
        }, "提交")
      ])
    ]);
  }
  const PagesSettingsPassword = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$6], ["__scopeId", "data-v-09e072c0"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/settings/password.vue"]]);
  const _sfc_main$6 = {
    data() {
      return {
        form: {
          username: "",
          email: ""
        }
      };
    },
    async onLoad() {
      const userInfo = uni.getStorageSync("userInfo") || {};
      this.form.username = userInfo.username || "";
      this.form.email = userInfo.email || "";
      try {
        const res = await userApi.getUserInfo();
        if ((res.success || res.status === "success") && res.data) {
          this.form.username = res.data.username || "";
          this.form.email = res.data.email || "";
        }
      } catch (error) {
        formatAppLog("error", "at pages/settings/email.vue:35", "email load failed", error);
      }
    },
    methods: {
      async save() {
        if (!this.form.email.trim()) {
          uni.showToast({ title: "请输入邮箱", icon: "none" });
          return;
        }
        const res = await userApi.updateProfile(this.form);
        if (res.status === "success" || res.success) {
          await userApi.syncCurrentUser();
          uni.showToast({ title: "邮箱已保存", icon: "success" });
        } else {
          uni.showToast({ title: res.message || "保存失败", icon: "none" });
        }
      }
    }
  };
  function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "page" }, [
      vue.createElementVNode("view", { class: "card" }, [
        vue.createElementVNode("text", { class: "label" }, "邮箱地址"),
        vue.withDirectives(vue.createElementVNode(
          "input",
          {
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.form.email = $event),
            class: "input",
            placeholder: "请输入邮箱地址"
          },
          null,
          512
          /* NEED_PATCH */
        ), [
          [vue.vModelText, $data.form.email]
        ]),
        vue.createElementVNode("view", {
          class: "btn",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.save && $options.save(...args))
        }, "保存邮箱")
      ])
    ]);
  }
  const PagesSettingsEmail = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$5], ["__scopeId", "data-v-810a0d72"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/settings/email.vue"]]);
  const _sfc_main$5 = {
    data() {
      return {
        searchKeyword: "",
        categories: [
          { id: "account", name: "账号问题", icon: "👤" },
          { id: "chat", name: "对话功能", icon: "💬" },
          { id: "model", name: "模型相关", icon: "🧠" },
          { id: "payment", name: "充值付费", icon: "💰" },
          { id: "agent", name: "智能体", icon: "🤖" },
          { id: "other", name: "其他问题", icon: "❓" }
        ],
        faqList: [
          {
            question: "如何修改个人信息？",
            answer: '进入"我的"页面，点击"设置"，选择"编辑资料"即可修改个人信息。',
            category: "account",
            expanded: false
          },
          {
            question: "如何切换AI模型？",
            answer: '在聊天页面，点击底部的"选择模型"按钮，即可选择不同的AI模型进行对话。',
            category: "chat",
            expanded: false
          },
          {
            question: "对话历史可以保存多久？",
            answer: '对话历史默认保存在本地，不会自动删除。您可以在"历史记录"中查看和管理。',
            category: "chat",
            expanded: false
          },
          {
            question: "如何使用智能体？",
            answer: "在首页或智能体页面选择感兴趣的智能体，点击进入后即可开始对话。每个智能体都有特定的功能和场景。",
            category: "agent",
            expanded: false
          },
          {
            question: "支持哪些AI模型？",
            answer: "目前支持GPT系列、Claude系列、Llama系列、通义千问等多种模型。具体可用的模型取决于您的账号权限。",
            category: "model",
            expanded: false
          },
          {
            question: "如何充值？",
            answer: '进入"我的"页面，点击"充值"按钮，选择充值金额和支付方式即可完成充值。',
            category: "payment",
            expanded: false
          }
        ],
        filteredFAQ: []
      };
    },
    onLoad() {
      this.filteredFAQ = this.faqList;
    },
    methods: {
      searchFAQ() {
        if (!this.searchKeyword) {
          this.filteredFAQ = this.faqList;
          return;
        }
        const keyword = this.searchKeyword.toLowerCase();
        this.filteredFAQ = this.faqList.filter(
          (item) => item.question.toLowerCase().includes(keyword) || item.answer.toLowerCase().includes(keyword)
        );
      },
      toggleFAQ(index) {
        this.filteredFAQ[index].expanded = !this.filteredFAQ[index].expanded;
      },
      selectCategory(cat) {
        this.filteredFAQ = this.faqList.filter((item) => item.category === cat.id);
      },
      contactOnline() {
        uni.showToast({ title: "客服系统接入中", icon: "none" });
      },
      contactPhone() {
        uni.makePhoneCall({
          phoneNumber: "400-888-8888",
          fail: () => {
            uni.showModal({
              title: "客服电话",
              content: "400-888-8888",
              showCancel: false
            });
          }
        });
      },
      contactEmail() {
        uni.setClipboardData({
          data: "support@lingyue-ai.com",
          success: () => {
            uni.showToast({ title: "邮箱已复制", icon: "success" });
          }
        });
      },
      goFeedback() {
        uni.navigateTo({ url: "/pages/help/feedback" });
      }
    }
  };
  function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "help-container" }, [
      vue.createElementVNode("view", { class: "search-section" }, [
        vue.createElementVNode("view", { class: "search-box" }, [
          vue.createElementVNode("text", { class: "search-icon" }, "🔍"),
          vue.withDirectives(vue.createElementVNode(
            "input",
            {
              type: "text",
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.searchKeyword = $event),
              placeholder: "搜索常见问题...",
              "placeholder-class": "placeholder",
              onInput: _cache[1] || (_cache[1] = (...args) => $options.searchFAQ && $options.searchFAQ(...args))
            },
            null,
            544
            /* NEED_HYDRATION, NEED_PATCH */
          ), [
            [vue.vModelText, $data.searchKeyword]
          ])
        ])
      ]),
      !$data.searchKeyword ? (vue.openBlock(), vue.createElementBlock("view", {
        key: 0,
        class: "category-section"
      }, [
        vue.createElementVNode("view", { class: "category-grid" }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.categories, (cat) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "category-item",
                key: cat.id,
                onClick: ($event) => $options.selectCategory(cat)
              }, [
                vue.createElementVNode(
                  "text",
                  { class: "category-icon" },
                  vue.toDisplayString(cat.icon),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode(
                  "text",
                  { class: "category-name" },
                  vue.toDisplayString(cat.name),
                  1
                  /* TEXT */
                )
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ])
      ])) : vue.createCommentVNode("v-if", true),
      vue.createElementVNode("view", { class: "faq-section" }, [
        vue.createElementVNode(
          "view",
          { class: "section-title" },
          vue.toDisplayString($data.searchKeyword ? "搜索结果" : "热门问题"),
          1
          /* TEXT */
        ),
        vue.createElementVNode("view", { class: "faq-list" }, [
          (vue.openBlock(true), vue.createElementBlock(
            vue.Fragment,
            null,
            vue.renderList($data.filteredFAQ, (item, index) => {
              return vue.openBlock(), vue.createElementBlock("view", {
                class: "faq-item",
                key: index,
                onClick: ($event) => $options.toggleFAQ(index)
              }, [
                vue.createElementVNode("view", { class: "faq-question" }, [
                  vue.createElementVNode("text", { class: "q-badge" }, "Q"),
                  vue.createElementVNode(
                    "text",
                    { class: "question-text" },
                    vue.toDisplayString(item.question),
                    1
                    /* TEXT */
                  ),
                  vue.createElementVNode(
                    "text",
                    {
                      class: vue.normalizeClass(["expand-icon", { expanded: item.expanded }])
                    },
                    "▼",
                    2
                    /* CLASS */
                  )
                ]),
                item.expanded ? (vue.openBlock(), vue.createElementBlock("view", {
                  key: 0,
                  class: "faq-answer"
                }, [
                  vue.createElementVNode("text", { class: "a-badge" }, "A"),
                  vue.createElementVNode(
                    "text",
                    { class: "answer-text" },
                    vue.toDisplayString(item.answer),
                    1
                    /* TEXT */
                  )
                ])) : vue.createCommentVNode("v-if", true)
              ], 8, ["onClick"]);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ])
      ]),
      vue.createElementVNode("view", { class: "contact-section" }, [
        vue.createElementVNode("view", { class: "contact-title" }, "还没找到答案？"),
        vue.createElementVNode("view", { class: "contact-buttons" }, [
          vue.createElementVNode("view", {
            class: "contact-btn",
            onClick: _cache[2] || (_cache[2] = (...args) => $options.contactOnline && $options.contactOnline(...args))
          }, [
            vue.createElementVNode("text", { class: "btn-icon" }, "💬"),
            vue.createElementVNode("text", { class: "btn-text" }, "在线客服")
          ]),
          vue.createElementVNode("view", {
            class: "contact-btn",
            onClick: _cache[3] || (_cache[3] = (...args) => $options.contactPhone && $options.contactPhone(...args))
          }, [
            vue.createElementVNode("text", { class: "btn-icon" }, "📞"),
            vue.createElementVNode("text", { class: "btn-text" }, "电话客服")
          ]),
          vue.createElementVNode("view", {
            class: "contact-btn",
            onClick: _cache[4] || (_cache[4] = (...args) => $options.contactEmail && $options.contactEmail(...args))
          }, [
            vue.createElementVNode("text", { class: "btn-icon" }, "📧"),
            vue.createElementVNode("text", { class: "btn-text" }, "邮件反馈")
          ])
        ])
      ]),
      vue.createElementVNode("view", {
        class: "feedback-section",
        onClick: _cache[5] || (_cache[5] = (...args) => $options.goFeedback && $options.goFeedback(...args))
      }, [
        vue.createElementVNode("text", { class: "feedback-text" }, "意见反馈"),
        vue.createElementVNode("text", { class: "feedback-arrow" }, "›")
      ])
    ]);
  }
  const PagesHelpHelp = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$4], ["__scopeId", "data-v-5194e907"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/help/help.vue"]]);
  const _sfc_main$4 = {
    data() {
      return {
        content: ""
      };
    },
    methods: {
      submit() {
        if (!this.content.trim()) {
          uni.showToast({ title: "请输入反馈内容", icon: "none" });
          return;
        }
        uni.showToast({ title: "反馈已提交", icon: "success" });
        this.content = "";
      }
    }
  };
  function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "page" }, [
      vue.createElementVNode("view", { class: "card" }, [
        vue.createElementVNode("text", { class: "title" }, "意见反馈"),
        vue.withDirectives(vue.createElementVNode(
          "textarea",
          {
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.content = $event),
            class: "textarea",
            placeholder: "请输入你的建议或问题"
          },
          null,
          512
          /* NEED_PATCH */
        ), [
          [vue.vModelText, $data.content]
        ]),
        vue.createElementVNode("view", {
          class: "btn",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.submit && $options.submit(...args))
        }, "提交反馈")
      ])
    ]);
  }
  const PagesHelpFeedback = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$3], ["__scopeId", "data-v-4e5b94c6"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/help/feedback.vue"]]);
  const _sfc_main$3 = {
    data() {
      return {
        scenarios: [
          { icon: "✍", title: "智能写作", desc: "快速生成文案、文章和总结", prompt: "请帮我写一篇关于人工智能的短文。", mode: "normal" },
          { icon: "🧠", title: "深度思考", desc: "复杂问题推理和分析", prompt: "请深入分析 AI 产品的核心竞争力。", mode: "deep_think" },
          { icon: "🌐", title: "联网搜索", desc: "需要结合网络信息回答", prompt: "请帮我搜索并总结这个问题。", mode: "web_search" },
          { icon: "🖼", title: "图像分析", desc: "进入聊天页后可切到图像理解模式", prompt: "请帮我分析上传的图片。", mode: "vision_analysis" }
        ]
      };
    },
    methods: {
      openScenario(item) {
        uni.switchTab({
          url: "/pages/chat/chat",
          success: () => {
            uni.$emit("setChatInput", item.prompt);
            if (item.mode && item.mode !== "normal") {
              uni.$emit("setChatMode", item.mode);
            }
          }
        });
      }
    }
  };
  function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "page" }, [
      vue.createElementVNode("view", { class: "header" }, [
        vue.createElementVNode("text", { class: "title" }, "场景列表"),
        vue.createElementVNode("text", { class: "desc" }, "选择一个常用场景并直接带入聊天页。")
      ]),
      vue.createElementVNode("view", { class: "list" }, [
        (vue.openBlock(true), vue.createElementBlock(
          vue.Fragment,
          null,
          vue.renderList($data.scenarios, (item) => {
            return vue.openBlock(), vue.createElementBlock("view", {
              key: item.title,
              class: "card",
              onClick: ($event) => $options.openScenario(item)
            }, [
              vue.createElementVNode(
                "text",
                { class: "icon" },
                vue.toDisplayString(item.icon),
                1
                /* TEXT */
              ),
              vue.createElementVNode("view", { class: "body" }, [
                vue.createElementVNode(
                  "text",
                  { class: "name" },
                  vue.toDisplayString(item.title),
                  1
                  /* TEXT */
                ),
                vue.createElementVNode(
                  "text",
                  { class: "text" },
                  vue.toDisplayString(item.desc),
                  1
                  /* TEXT */
                )
              ])
            ], 8, ["onClick"]);
          }),
          128
          /* KEYED_FRAGMENT */
        ))
      ])
    ]);
  }
  const PagesScenariosScenarios = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$2], ["__scopeId", "data-v-42be7276"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/scenarios/scenarios.vue"]]);
  const _sfc_main$2 = {
    methods: {
      goBack() {
        uni.navigateBack();
      }
    }
  };
  function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "privacy-container" }, [
      vue.createElementVNode("view", { class: "nav-bar" }, [
        vue.createElementVNode("view", {
          class: "nav-back",
          onClick: _cache[0] || (_cache[0] = (...args) => $options.goBack && $options.goBack(...args))
        }, [
          vue.createElementVNode("text", { class: "icon" }, "←")
        ]),
        vue.createElementVNode("text", { class: "nav-title" }, "隐私政策"),
        vue.createElementVNode("view", { class: "nav-right" })
      ]),
      vue.createElementVNode("scroll-view", {
        "scroll-y": "",
        class: "content"
      }, [
        vue.createElementVNode("view", { class: "section" }, [
          vue.createElementVNode("text", { class: "section-title" }, "隐私政策"),
          vue.createElementVNode("text", { class: "update-time" }, "最后更新日期：2024年1月"),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "1. 引言"),
            vue.createElementVNode("text", { class: "p-content" }, ' 凌岳AI助手（以下简称"我们"或"本应用"）非常重视用户的隐私保护。本隐私政策说明了我们如何收集、使用、存储和保护您的个人信息。请您在使用本应用前仔细阅读本政策。 ')
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "2. 信息收集"),
            vue.createElementVNode("text", { class: "p-content" }, " 我们可能收集以下信息： • 账户信息：用户名、邮箱地址等注册信息 • 使用数据：对话记录、功能使用频率、Token消耗量 • 设备信息：设备型号、操作系统版本、IP地址 • 日志信息：访问时间、使用时长、错误日志 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "3. 信息使用"),
            vue.createElementVNode("text", { class: "p-content" }, " 我们使用收集的信息用于： • 提供、维护和改进本应用服务 • 处理您的请求和反馈 • 发送服务通知和更新信息 • 防止欺诈和滥用行为 • 进行数据分析和研究以改善用户体验 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "4. 本地部署说明"),
            vue.createElementVNode("text", { class: "p-content" }, " 凌岳AI助手采用本地部署架构： • AI模型运行在您的本地服务器或设备上 • 对话数据主要存储在本地数据库 • 不会将您的私人数据上传到第三方云服务 • 您对自己的数据拥有完全控制权 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "5. 数据安全"),
            vue.createElementVNode("text", { class: "p-content" }, " 我们采取以下措施保护您的数据： • 使用行业标准的加密技术保护数据传输 • 实施严格的访问控制和身份验证 • 定期进行安全审计和漏洞扫描 • 建立数据备份和恢复机制 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "6. 用户权利"),
            vue.createElementVNode("text", { class: "p-content" }, " 您拥有以下权利： • 访问和查看您的个人信息 • 更正不准确的个人信息 • 删除您的账户和相关数据 • 导出您的数据副本 • 随时撤回同意（可能影响服务使用） ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "7. 第三方服务"),
            vue.createElementVNode("text", { class: "p-content" }, " 本应用可能集成第三方服务（如在线AI模型API），这些服务有其独立的隐私政策。我们建议您阅读相关第三方的隐私政策，了解他们如何处理您的数据。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "8. 政策更新"),
            vue.createElementVNode("text", { class: "p-content" }, " 我们可能会不时更新本隐私政策。更新后的政策将在本应用内公布，重大变更我们会通过适当方式通知您。继续使用本应用即表示您同意更新后的政策。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "9. 联系我们"),
            vue.createElementVNode("text", { class: "p-content" }, " 如果您对本隐私政策有任何疑问或建议，请通过以下方式联系我们： • 邮箱：1293724438@qq.com • 地址：凌岳科技有限公司 ")
          ]),
          vue.createElementVNode("view", { class: "bottom-space" })
        ])
      ])
    ]);
  }
  const PagesSettingsPrivacy = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$1], ["__scopeId", "data-v-5ce191da"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/settings/privacy.vue"]]);
  const _sfc_main$1 = {
    methods: {
      goBack() {
        uni.navigateBack();
      }
    }
  };
  function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
    return vue.openBlock(), vue.createElementBlock("view", { class: "terms-container" }, [
      vue.createElementVNode("view", { class: "nav-bar" }, [
        vue.createElementVNode("view", {
          class: "nav-back",
          onClick: _cache[0] || (_cache[0] = (...args) => $options.goBack && $options.goBack(...args))
        }, [
          vue.createElementVNode("text", { class: "icon" }, "←")
        ]),
        vue.createElementVNode("text", { class: "nav-title" }, "用户协议"),
        vue.createElementVNode("view", { class: "nav-right" })
      ]),
      vue.createElementVNode("scroll-view", {
        "scroll-y": "",
        class: "content"
      }, [
        vue.createElementVNode("view", { class: "section" }, [
          vue.createElementVNode("text", { class: "section-title" }, "用户协议"),
          vue.createElementVNode("text", { class: "update-time" }, "最后更新日期：2024年1月"),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "1. 协议接受"),
            vue.createElementVNode("text", { class: "p-content" }, ' 欢迎使用凌岳AI助手！请您在使用本应用前仔细阅读本用户协议（以下简称"本协议"）。通过下载、安装、注册或使用本应用，即表示您已阅读、理解并同意接受本协议的所有条款。如果您不同意本协议的任何内容，请立即停止使用本应用。 ')
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "2. 服务说明"),
            vue.createElementVNode("text", { class: "p-content" }, " 凌岳AI助手是一款基于人工智能技术的智能对话应用，提供以下服务： • AI智能对话和问答 • 文本生成和内容创作 • 代码辅助和编程支持 • 智能体创建和管理 • 多模型切换和对比 • 文件分析和处理 本应用支持本地部署和在线API两种模式，用户可根据需求选择合适的使用方式。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "3. 账户注册与安全"),
            vue.createElementVNode("text", { class: "p-content" }, " 3.1 您需要注册账户才能使用本应用的全部功能。注册时您需要提供真实、准确、完整的个人信息，并及时更新以保持信息的有效性。 3.2 您有责任保护账户安全，妥善保管登录密码。如发现账户异常，请立即通知我们。 3.3 禁止转让、借用或共享账户。每个用户只能拥有一个主账户。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "4. 使用规范"),
            vue.createElementVNode("text", { class: "p-content" }, " 您同意在使用本应用时遵守以下规范： • 遵守所有适用的法律法规 • 尊重他人的知识产权和隐私权 • 不生成、传播违法、有害、侵权内容 • 不进行任何形式的网络攻击或滥用 • 不干扰或破坏本应用的正常运行 • 不使用自动化工具批量访问API 违反上述规范可能导致账户暂停或终止。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "5. 知识产权"),
            vue.createElementVNode("text", { class: "p-content" }, " 5.1 本应用的界面设计、代码、文档等知识产权归凌岳科技有限公司所有。 5.2 您使用本应用生成的内容，其知识产权归您所有。但请注意： • AI生成内容可能受第三方知识产权约束 • 请勿将生成内容用于非法或侵权用途 • 商业使用前建议进行知识产权审查 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "6. 服务变更与中断"),
            vue.createElementVNode("text", { class: "p-content" }, " 6.1 我们保留随时修改、暂停或终止部分或全部服务的权利，会尽可能提前通知用户。 6.2 以下情况可能导致服务中断： • 系统维护或升级 • 不可抗力因素 • 违反本协议导致的账户封禁 对于非我们原因导致的服务中断，我们不承担责任。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "7. 免责声明"),
            vue.createElementVNode("text", { class: "p-content" }, " 7.1 AI生成内容的准确性和适用性无法完全保证，仅供参考，不构成专业建议。 7.2 本地部署模式下，数据安全由用户自行负责。请妥善保管服务器访问权限。 7.3 因用户自身原因（如密码泄露、设备丢失）造成的损失，我们不承担责任。 7.4 在法律允许的最大范围内，我们对间接、附带、特殊损失不承担责任。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "8. 协议修改"),
            vue.createElementVNode("text", { class: "p-content" }, " 我们可能会不时修改本协议。修改后的协议将在本应用内公布，重大变更我们会通过适当方式通知您。继续使用本应用即表示您接受修改后的协议。如您不同意修改内容，请停止使用本应用。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "9. 争议解决"),
            vue.createElementVNode("text", { class: "p-content" }, " 9.1 本协议的订立、执行和解释均适用中华人民共和国法律。 9.2 如发生争议，双方应友好协商解决；协商不成的，任何一方可向被告所在地人民法院提起诉讼。 ")
          ]),
          vue.createElementVNode("view", { class: "paragraph" }, [
            vue.createElementVNode("text", { class: "p-title" }, "10. 其他条款"),
            vue.createElementVNode("text", { class: "p-content" }, " 10.1 本协议构成双方之间关于本应用使用的完整协议，取代之前的所有口头或书面协议。 10.2 如本协议任何条款被认定为无效或不可执行，不影响其他条款的效力。 10.3 如有任何问题或建议，请联系我们： • 邮箱：1293724438@qq.com ")
          ]),
          vue.createElementVNode("view", { class: "bottom-space" })
        ])
      ])
    ]);
  }
  const PagesSettingsTerms = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render], ["__scopeId", "data-v-c6714e62"], ["__file", "E:/巨神兵本地包/gpustack-uniapp/pages/settings/terms.vue"]]);
  __definePage("pages/login/login", PagesLoginLogin);
  __definePage("pages/index/index", PagesIndexIndex);
  __definePage("pages/chat/chat", PagesChatChat);
  __definePage("pages/agents/agents", PagesAgentsAgents);
  __definePage("pages/agent-detail/agent-detail", PagesAgentDetailAgentDetail);
  __definePage("pages/agent-create/agent-create", PagesAgentCreateAgentCreate);
  __definePage("pages/mine/mine", PagesMineMine);
  __definePage("pages/collections/collections", PagesCollectionsCollections);
  __definePage("pages/history/history", PagesHistoryHistory);
  __definePage("pages/settings/settings", PagesSettingsSettings);
  __definePage("pages/settings/profile", PagesSettingsProfile);
  __definePage("pages/settings/password", PagesSettingsPassword);
  __definePage("pages/settings/email", PagesSettingsEmail);
  __definePage("pages/help/help", PagesHelpHelp);
  __definePage("pages/help/feedback", PagesHelpFeedback);
  __definePage("pages/scenarios/scenarios", PagesScenariosScenarios);
  __definePage("pages/settings/privacy", PagesSettingsPrivacy);
  __definePage("pages/settings/terms", PagesSettingsTerms);
  const _sfc_main = {
    globalData: {
      currentAgent: null,
      userInfo: null,
      config: null
    },
    onLaunch() {
      const token = uni.getStorageSync("token");
      if (!token) {
        uni.redirectTo({ url: "/pages/login/login" });
        return;
      }
      this.syncUserSession();
    },
    onShow() {
      if (uni.getStorageSync("token")) {
        this.syncUserSession();
      }
    },
    methods: {
      async syncUserSession() {
        try {
          const user = await userApi.syncCurrentUser();
          this.globalData.userInfo = user;
        } catch (error) {
          uni.redirectTo({ url: "/pages/login/login" });
        }
      }
    }
  };
  const App = /* @__PURE__ */ _export_sfc(_sfc_main, [["__file", "E:/巨神兵本地包/gpustack-uniapp/App.vue"]]);
  function createApp() {
    const app = vue.createVueApp(App);
    return {
      app
    };
  }
  const { app: __app__, Vuex: __Vuex__, Pinia: __Pinia__ } = createApp();
  uni.Vuex = __Vuex__;
  uni.Pinia = __Pinia__;
  __app__.provide("__globalStyles", __uniConfig.styles);
  __app__._component.mpType = "app";
  __app__._component.render = () => {
  };
  __app__.mount("#app");
})(Vue);
