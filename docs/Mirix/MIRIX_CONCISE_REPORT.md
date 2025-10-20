# MIRIX: Multi-Agent Memory System for LLM-Based Agents - Executive Summary

**Source**: [arXiv:2507.07957v1](https://arxiv.org/html/2507.07957v1)
**Authors**: Yu Wang, Xi Chen (MIRIX AI)
**Published**: July 2025
**Report Date**: October 19, 2025

---

## 📋 What is MIRIX?

MIRIX is a groundbreaking modular, multi-agent memory system published in July 2025 that solves a critical challenge in AI: enabling language models to **truly remember** across sessions with multimodal support (text + images).

---

## 🎯 Core Problem Addressed

Current LLM agents are essentially **stateless** - they forget everything after each session. Existing memory solutions have three major flaws:

1. **Flat memory structure** - Everything stored in one bucket without organization
2. **Text-only** - Cannot handle images, screenshots, or visual data
3. **Poor scalability** - Storing raw data (especially images) creates massive storage overhead

---

## 🏗️ MIRIX Architecture

### Six Specialized Memory Components:

1. **Core Memory** - User preferences and essential context
2. **Episodic Memory** - Specific events and experiences (e.g., "Last week's meeting")
3. **Semantic Memory** - Concepts, entities, definitions (e.g., "Who is John Smith?")
4. **Procedural Memory** - Step-by-step instructions (e.g., "How to deploy the app")
5. **Resource Memory** - Documents, files, media shared by users
6. **Knowledge Vault** - Critical verbatim information (addresses, phone numbers, credentials)

### Multi-Agent Framework:

- **6 Memory Managers** - One specialized agent per memory type
- **1 Meta Memory Manager** - Routes information to appropriate memory components
- **1 Chat Agent** - User interface with **Active Retrieval** mechanism

**Active Retrieval Innovation**: Before answering, the agent generates a "topic" to retrieve relevant memories, making retrieval context-aware and dynamic.

---

## 📊 Benchmark Performance

### ScreenshotVQA (Multimodal Benchmark):

- **Task**: Process 5,000-20,000 high-resolution screenshots from one month of computer usage
- **Results vs RAG baseline**:
  - ✅ **35% higher accuracy**
  - ✅ **99.9% storage reduction**
- **Results vs Long-context (Gemini)**:
  - ✅ **410% accuracy improvement**
  - ✅ **93.3% storage reduction**

### LOCOMO (Text Conversation Benchmark):

- **State-of-the-art**: **85.4% accuracy**
- **Improvement**: **8% better** than previous best method (Mem0)
- Approaches the upper bound of full-context models

---

## 🔬 Technical Implementation

- **Backbone Model**: GPT-4.1-mini (superior function-calling capabilities)
- **Multimodal Processing**: Gemini 2.5 Flash for visual understanding
- **Storage**: SQLite database (extremely compact - ~16MB vs gigabytes of raw images)
- **Update Frequency**: Every ~60 seconds (after 20 unique screenshots)
- **Latency**: Under 5 seconds using streaming upload to Google Cloud

---

## 💡 Key Innovation: Why It Works

1. **Routing** - Intelligently directs information to the right memory type
2. **Abstraction** - Extracts meaning instead of storing raw data
3. **Hierarchical Structure** - Each memory type has internal organization (summary, details, timestamps)
4. **Multimodal Native** - Built from the ground up for visual + text data

---

## 🚀 Real-World Application

MIRIX released a **cross-platform desktop app** (React-Electron) that:

- Captures screenshots every 1.5 seconds
- Builds a personalized memory automatically
- Provides visual memory exploration (tree structures, lists)
- Ensures **local storage** for privacy
- Enables natural language queries about past activities

### Use Cases:

- **Personal assistants** - "What project was I working on last Tuesday?"
- **Wearable devices** - Smart glasses, AI pins that remember context
- **Enterprise** - Meeting summarization, client relationship tracking

---

## 📈 2025 October Trends & Best Practices

### Industry Shift:

1. **From Single LLMs → Multi-Agent Systems** (2023-2024 → 2025)
2. **Agentic Memory** - Agents that learn and adapt from feedback
3. **Context Engineering** - Optimizing memory for 128K-10M token windows

### Competing Approaches (October 2025):

| System | Key Feature | Performance |
|--------|-------------|-------------|
| **A-MEM** | Zettelkasten-inspired dynamic organization | Adaptive indexing |
| **Mem0** | Graph-based relationships, production-ready | 26% better than OpenAI Memory |
| **LangMem** | LangChain toolkit for memory extraction | Modular tools |
| **Zep** | Knowledge graph approach | Structured relationships |
| **MIRIX** | Six memory types + multimodal | SOTA on LOCOMO (85.4%) |

### Best Practices Emerging:

1. **Specialized Memory Types** - Move beyond "short-term" and "long-term"
2. **Multi-Modal Support** - Visual data is critical for real-world scenarios
3. **Dynamic Organization** - Self-organizing memory networks (Zettelkasten method)
4. **Function Calling** - Strong tool-use capabilities essential for memory management
5. **Local + Secure** - Privacy-first with on-device storage
6. **Streaming Architecture** - Reduce latency through asynchronous processing
7. **Abstraction Over Storage** - Extract meaning, not raw data

---

## 🔮 Future Implications

- **Enterprise Intelligence**: Multi-agent memory systems becoming foundational to business AI
- **Wearables**: AI glasses and pins with persistent, evolving memory
- **Context Windows**: Despite 10M token models, specialized memory still outperforms raw context
- **Memory Marketplace**: Future vision of shareable/transferable agent memories

---

## ⚠️ Challenges Remaining

1. **Memory Balance** - Too much memory can be harmful; field seeking optimal amount
2. **Inter-Agent Coordination** - Communication breakdowns in multi-agent systems
3. **Cross-Session Consistency** - Maintaining coherent memory across long time periods
4. **Privacy & Security** - Handling sensitive information in memory stores

---

## 📚 Key Takeaways

- **MIRIX represents state-of-the-art** in memory-augmented LLM agents as of July 2025
- **Multimodal support is differentiating** - Most systems still text-only
- **Compositional memory architecture** beats flat storage by massive margins
- **Production-ready** - Not just research, but deployable application available
- **October 2025 trend**: Industry moving toward specialized, adaptive, multi-agent memory systems

---

## 🔗 Resources

- **Paper**: https://arxiv.org/html/2507.07957v1
- **Website**: https://mirix.io/
- **GitHub**: https://github.com/Mirix-AI/MIRIX
- **Evaluation Code**: https://github.com/Mirix-AI/MIRIX/tree/public_evaluation

---

**Bottom Line**: MIRIX demonstrates that intelligent memory organization + multimodal support + multi-agent coordination can achieve 35-410% performance improvements while using 99.9% less storage than naive approaches. This represents a paradigm shift from "storing everything" to "remembering intelligently."
